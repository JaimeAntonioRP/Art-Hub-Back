<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de información o intención de compra sobre una obra (§14). */
class CommercialRequest extends Model
{
    public const TYPES = ['informacion', 'intencion_compra'];

    public const TYPE_LABELS = [
        'informacion' => 'Información',
        'intencion_compra' => 'Intención de compra',
    ];

    public const STATUSES = ['nueva', 'contactado', 'en_evaluacion', 'confirmada', 'cancelada'];

    public const STATUS_NUEVA = 'nueva';

    public const STATUS_LABELS = [
        'nueva' => 'Nueva',
        'contactado' => 'Contactado',
        'en_evaluacion' => 'En evaluación',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
    ];

    protected $fillable = ['artwork_id', 'user_id', 'name', 'email', 'phone', 'type', 'message', 'status', 'admin_notes'];

    protected $attributes = [
        'status' => self::STATUS_NUEVA,
        'type' => 'informacion',
    ];

    protected static function booted(): void
    {
        // En el historial de la obra solo queda el tipo y el estado: nunca los datos del interesado (§24).
        static::created(fn (CommercialRequest $r) => $r->artwork?->recordEvent('solicitud_recibida', ['tipo' => $r->type]));

        static::updated(function (CommercialRequest $r) {
            if ($r->wasChanged('status')) {
                $r->artwork?->recordEvent('solicitud_actualizada', [
                    'tipo' => $r->type,
                    'de'   => $r->getOriginal('status'),
                    'a'    => $r->status,
                ]);
            }
        });
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
