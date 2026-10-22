<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Registro manual de una reserva, venta o transferencia (§26). El pago se coordina fuera
 * de la plataforma; aquí queda constancia y se actualizan la disponibilidad y el propietario.
 */
class Sale extends Model
{
    public const TYPE_RESERVA = 'reserva';
    public const TYPE_VENTA = 'venta';
    public const TYPE_TRANSFERENCIA = 'transferencia';

    public const TYPE_LABELS = [
        self::TYPE_RESERVA       => 'Reserva',
        self::TYPE_VENTA         => 'Venta',
        self::TYPE_TRANSFERENCIA => 'Transferencia',
    ];

    /** Disponibilidad desde la que se permite cada registro, y la que queda después. */
    private const RULES = [
        self::TYPE_RESERVA       => ['from' => ['disponible'], 'to' => 'reservada'],
        self::TYPE_VENTA         => ['from' => ['disponible', 'reservada'], 'to' => 'vendida'],
        self::TYPE_TRANSFERENCIA => ['from' => ['vendida', 'no_venta'], 'to' => null], // no cambia
    ];

    private const RULE_ERRORS = [
        self::TYPE_RESERVA       => 'Solo se reserva una obra disponible.',
        self::TYPE_VENTA         => 'Solo se vende una obra disponible o reservada.',
        self::TYPE_TRANSFERENCIA => 'La transferencia es para obras que ya no están a la venta (vendidas o no a la venta). Para una obra disponible, registra una venta.',
    ];

    protected $fillable = [
        'artwork_id', 'commercial_request_id', 'type', 'date', 'final_price', 'currency',
        'party_name', 'party_document', 'party_email', 'party_phone', 'notes',
        'availability_before', 'registered_by', 'cancelled_at', 'cancelled_by', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'final_price'  => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    /** Registros posibles según la disponibilidad actual de la obra. */
    public static function allowedTypesFor(Artwork $artwork): array
    {
        return array_keys(array_filter(self::RULES, fn (array $rule) => in_array($artwork->availability, $rule['from'], true)));
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function commercialRequest(): BelongsTo
    {
        return $this->belongsTo(CommercialRequest::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function ownership(): HasOne
    {
        return $this->hasOne(Ownership::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /** Venta o transferencia: la obra cambia de propietario. */
    public function changesOwner(): bool
    {
        return $this->type !== self::TYPE_RESERVA;
    }

    /**
     * Registra el movimiento y aplica sus efectos: disponibilidad, propietario, estado de la
     * solicitud de origen e historial de la obra. Todo o nada.
     *
     * @param  array{type: string, date: string, party_name: string, final_price?: mixed, currency?: string, party_document?: string, party_email?: string, party_phone?: string, notes?: string}  $data
     */
    public static function register(Artwork $artwork, array $data, User $by, ?CommercialRequest $request = null): self
    {
        $rule = self::RULES[$data['type']] ?? throw new InvalidArgumentException('Tipo de registro desconocido.');

        if (! in_array($artwork->availability, $rule['from'], true)) {
            throw new DomainException(self::RULE_ERRORS[$data['type']]);
        }
        if ($request && $request->artwork_id !== $artwork->id) {
            throw new InvalidArgumentException('La solicitud es de otra obra.');
        }

        return DB::transaction(function () use ($artwork, $data, $by, $request, $rule) {
            $sale = $artwork->sales()->create([
                ...$data,
                'currency'              => isset($data['final_price']) && $data['final_price'] !== null ? ($data['currency'] ?? $artwork->currency) : null,
                'commercial_request_id' => $request?->id,
                'availability_before'   => $artwork->availability,
                'registered_by'         => $by->id,
            ]);

            if ($sale->changesOwner()) {
                Ownership::ensureInitial($artwork);
                $artwork->ownerships()->whereNull('to_date')->update(['to_date' => $sale->date]);
                $artwork->ownerships()->create([
                    'sale_id'        => $sale->id,
                    'owner_name'     => $sale->party_name,
                    'owner_document' => $sale->party_document,
                    'owner_email'    => $sale->party_email,
                    'origin'         => $sale->type,
                    'from_date'      => $sale->date,
                ]);
            }

            if ($rule['to'] && $artwork->availability !== $rule['to']) {
                $artwork->update(['availability' => $rule['to']]);
            }

            if ($request) {
                $request->update(['status' => $sale->type === self::TYPE_VENTA ? 'confirmada' : 'en_evaluacion']);
            }

            // En el historial no van los datos del comprador: el artista también lo ve.
            $artwork->recordEvent("{$sale->type}_registrada", ['fecha' => $sale->date->toDateString()], $by->id);

            return $sale;
        });
    }

    /**
     * Anula un registro equivocado y deshace sus efectos. Solo el último registro vigente de
     * la obra, para que la disponibilidad y el propietario vuelvan a un estado coherente.
     */
    public function cancel(User $by, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Anular un registro necesita un motivo.');
        }
        if ($this->isCancelled()) {
            throw new DomainException('Este registro ya está anulado.');
        }
        $latest = $this->artwork->sales()->whereNull('cancelled_at')->latest('id')->first();
        if (! $latest?->is($this)) {
            throw new DomainException('Solo se anula el último registro vigente de la obra.');
        }

        DB::transaction(function () use ($by, $reason) {
            $artwork = $this->artwork;

            if ($this->changesOwner()) {
                $this->ownership?->delete();
                $artwork->ownerships()->latest('id')->first()?->update(['to_date' => null]);
            }

            if ($artwork->availability !== $this->availability_before) {
                $artwork->update(['availability' => $this->availability_before]);
            }

            $this->update(['cancelled_at' => now(), 'cancelled_by' => $by->id, 'cancel_reason' => $reason]);

            $artwork->recordEvent('registro_anulado', [
                'tipo'   => $this->type,
                'fecha'  => $this->date->toDateString(),
                'motivo' => $reason,
            ], $by->id);
        });
    }
}
