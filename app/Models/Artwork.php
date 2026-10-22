<?php

namespace App\Models;

use App\Notifications\ArtworkReviewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Artwork extends Model
{
    /** Estado editorial (§10). */
    public const STATUSES = ['borrador', 'en_revision', 'observado', 'rechazado', 'aprobado', 'publicado', 'despublicado'];
    public const STATUS_PUBLICADO = 'publicado';

    /** Disponibilidad comercial, independiente del estado editorial. */
    public const AVAILABILITIES = ['disponible', 'reservada', 'vendida', 'no_venta'];

    public const CURRENCIES = ['PEN', 'USD'];

    public const STATUS_BORRADOR = 'borrador';
    public const STATUS_EN_REVISION = 'en_revision';
    public const STATUS_OBSERVADO = 'observado';
    public const STATUS_RECHAZADO = 'rechazado';
    public const STATUS_APROBADO = 'aprobado';
    public const STATUS_DESPUBLICADO = 'despublicado';

    /**
     * Flujo de revisión (§10): estado actual => estados a los que puede pasar cada rol.
     * El artista envía y subsana; el administrador revisa y publica. Las obras que
     * carga el propio admin (p. ej. de artistas sin cuenta) pueden publicarse sin envío.
     */
    private const ARTIST_TRANSITIONS = [
        'borrador'  => ['en_revision'],
        'observado' => ['en_revision'],
    ];

    private const ADMIN_TRANSITIONS = [
        'borrador'     => ['aprobado', 'publicado'],
        'en_revision'  => ['aprobado', 'observado', 'rechazado', 'publicado'],
        'observado'    => ['aprobado', 'rechazado'],
        'rechazado'    => ['borrador'],
        'aprobado'     => ['publicado', 'observado'],
        'publicado'    => ['despublicado'],
        'despublicado' => ['publicado', 'borrador'],
    ];

    /** Estados en los que el administrador debe dejar un motivo. */
    private const NEEDS_NOTE = ['observado', 'rechazado'];

    /** El artista solo edita mientras la obra no está en manos del equipo de revisión. */
    public const ARTIST_EDITABLE = ['borrador', 'observado'];

    public const STATUS_LABELS = [
        'borrador'     => 'Borrador',
        'en_revision'  => 'En revisión',
        'observado'    => 'Observado',
        'rechazado'    => 'Rechazado',
        'aprobado'     => 'Aprobado',
        'publicado'    => 'Publicado',
        'despublicado' => 'Despublicado',
    ];

    public const AVAILABILITY_LABELS = [
        'disponible' => 'Disponible',
        'reservada'  => 'Reservada',
        'vendida'    => 'Vendida',
        'no_venta'   => 'No a la venta',
    ];

    protected $fillable = [
        'artist_id',
        'title',
        'year',
        'technique',
        'support',
        'height_cm',
        'width_cm',
        'depth_cm',
        'weight_kg',
        'description',
        'price',
        'currency',
        'show_price',
        'availability',
        'location',
        'status',
        'history_notes',
        'model_3d_url',
        // Campos de compatibilidad con los formularios actuales (ver accessors).
        'artist_name',
        'image_url',
        'dimensions',
    ];

    /** Mismos valores por defecto que la tabla, para que el historial vea el estado inicial. */
    protected $attributes = [
        'status'       => 'borrador',
        'availability' => 'disponible',
        'currency'     => 'USD',
        'show_price'   => true,
    ];

    // user_id hace falta para avisar al artista por correo (Artwork::transitionTo).
    protected $with = ['artist:id,user_id,name,slug,origin', 'images'];

    protected $appends = ['artist_name', 'image_url', 'dimensions'];

    /** URL de imagen recibida por el campo de compatibilidad; se guarda como imagen principal. */
    private ?string $pendingImageUrl = null;

    /** Nota de la revisión en curso; se guarda en el evento del historial. */
    private ?string $pendingNote = null;

    protected function casts(): array
    {
        return [
            'year'         => 'integer',
            'price'        => 'decimal:2',
            'height_cm'    => 'decimal:2',
            'width_cm'     => 'decimal:2',
            'depth_cm'     => 'decimal:2',
            'weight_kg'    => 'decimal:2',
            'show_price'   => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Artwork $artwork) {
            if ($artwork->status === self::STATUS_PUBLICADO && ! $artwork->published_at) {
                $artwork->published_at = now();
            }
        });

        static::created(function (Artwork $artwork) {
            if (! $artwork->code) {
                $artwork->code = self::buildCode($artwork->artist?->origin, $artwork->created_at, $artwork->id);
                $artwork->saveQuietly();
            }
            $artwork->recordEvent('creada');
        });

        static::updated(function (Artwork $artwork) {
            if ($artwork->wasChanged('status')) {
                $artwork->recordEvent('estado_cambiado', array_filter([
                    'de'   => $artwork->getOriginal('status'),
                    'a'    => $artwork->status,
                    'nota' => $artwork->pendingNote,
                ]));
            }
            if ($artwork->wasChanged('availability')) {
                $artwork->recordEvent('disponibilidad_cambiada', [
                    'de' => $artwork->getOriginal('availability'),
                    'a'  => $artwork->availability,
                ]);
            }

            $edited = array_diff(
                array_keys($artwork->getChanges()),
                ['status', 'availability', 'published_at', 'code', 'updated_at'],
            );
            if ($edited) {
                $artwork->recordEvent('editada', ['campos' => array_values($edited)]);
            }
        });

        static::saved(function (Artwork $artwork) {
            $artwork->syncPendingImage();
        });

        // La base de datos borra las filas en cascada, pero sin eventos: los archivos quedarían en el disco.
        static::deleting(function (Artwork $artwork) {
            $artwork->images()->get()->each->delete();
            $artwork->documents()->get()->each->delete();
        });
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ArtworkImage::class)->orderBy('position')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ArtworkEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function reviewNotes(): MorphMany
    {
        return $this->morphMany(ReviewNote::class, 'reviewable');
    }

    public function commercialRequests(): HasMany
    {
        return $this->hasMany(CommercialRequest::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** El último certificado emitido (vigente, anulado o reemplazado). */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class)->latestOfMany();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLICADO);
    }

    /** Datos públicos para tarjetas del catálogo y de obras relacionadas. */
    public function toCard(): array
    {
        return [
            ...$this->only(['id', 'code', 'title', 'technique', 'year', 'price', 'currency', 'show_price', 'availability', 'model_3d_url']),
            'artist_name' => $this->artist_name,
            'artist_slug' => $this->artist?->slug,
            'image_url' => $this->image_url,
            'dimensions' => $this->dimensions,
        ];
    }

    public function primaryImage(): ?ArtworkImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function canTransitionTo(string $status, User $by): bool
    {
        $map = $by->isAdmin() ? self::ADMIN_TRANSITIONS : self::ARTIST_TRANSITIONS;

        return in_array($status, $map[$this->status] ?? [], true);
    }

    /** Lo que falta para enviar la obra a revisión (vacío si está completa). */
    public function missingForReview(): array
    {
        return array_keys(array_filter([
            'una imagen' => $this->images()->doesntExist(),
            'el alto y el ancho' => ! $this->height_cm || ! $this->width_cm,
            'la técnica' => blank($this->technique),
        ]));
    }

    /**
     * Cambia el estado editorial, guarda la observación, deja el evento en el
     * historial y, si decide el admin, avisa al artista por correo.
     */
    public function transitionTo(string $status, User $by, ?string $note = null): void
    {
        if (! $this->canTransitionTo($status, $by)) {
            throw new InvalidArgumentException("No se puede pasar de {$this->status} a {$status}.");
        }

        $note = trim((string) $note);
        if (in_array($status, self::NEEDS_NOTE, true) && $note === '') {
            throw new InvalidArgumentException('Indica el motivo de la observación o el rechazo.');
        }
        if ($status === self::STATUS_EN_REVISION && $missing = $this->missingForReview()) {
            throw new InvalidArgumentException('Antes de enviarla, agrega '.implode(', ', $missing).'.');
        }

        DB::transaction(function () use ($status, $by, $note) {
            $this->pendingNote = $note ?: null;
            $this->update(['status' => $status]);
            $this->pendingNote = null;

            if ($note !== '') {
                $this->reviewNotes()->create(['author_id' => $by->id, 'body' => $note]);
            }
            // Al reenviar, las observaciones anteriores quedan como subsanadas.
            if ($status === self::STATUS_EN_REVISION) {
                $this->reviewNotes()->whereNull('resolved_at')->update(['resolved_at' => now()]);
            }
        });

        if ($by->isAdmin() && $this->artist?->user && ! $this->artist->user->is($by)) {
            $this->artist->user->notify(new ArtworkReviewed($this, $note ?: null));
        }
    }

    /** Agrega un evento al historial. Por defecto el autor es quien tiene la sesión abierta. */
    public function recordEvent(string $type, ?array $payload = null, ?int $actorId = null): void
    {
        $this->history()->create([
            'actor_id' => $actorId ?? auth()->id(),
            'type'     => $type,
            'payload'  => $payload,
        ]);
    }

    protected function artistName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->artist?->name,
            set: fn (?string $value) => ['artist_id' => Artist::where('name', $value)->value('id')],
        );
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->primaryImage()?->url,
            set: function (?string $value) {
                $this->pendingImageUrl = $value;

                return [];
            },
        );
    }

    protected function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn () => self::formatDimensions($this->height_cm, $this->width_cm, $this->depth_cm),
            set: fn (?string $value) => self::parseDimensions($value),
        );
    }

    private function syncPendingImage(): void
    {
        $url = $this->pendingImageUrl;
        $this->pendingImageUrl = null;
        if (! $url) {
            return;
        }

        $primary = $this->images()->where('is_primary', true)->first();
        if ($primary) {
            $primary->update(['path' => $url]);
        } else {
            $this->images()->create([
                'kind'       => 'principal',
                'path'       => $url,
                'position'   => 0,
                'is_primary' => true,
            ]);
        }
        $this->unsetRelation('images');
    }

    /**
     * Código único de la obra (§4): AH-{3 letras de la procedencia}-{año de registro}-{id}.
     * Regla provisional hasta que el cliente la confirme.
     */
    public static function buildCode(?string $origin, mixed $createdAt, int $id): string
    {
        $place = trim(explode(',', (string) $origin)[0]);
        $region = substr(preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($place))), 0, 3);
        if (strlen($region) < 3) {
            $region = 'PER';
        }
        $year = $createdAt ? Carbon::parse($createdAt)->year : now()->year;

        return sprintf('AH-%s-%d-%06d', $region, $year, $id);
    }

    /** "90 × 120 cm" → alto 90, ancho 120 (y profundidad si hay un tercer valor). */
    public static function parseDimensions(?string $text): array
    {
        $dims = ['height_cm' => null, 'width_cm' => null, 'depth_cm' => null];
        $num = '(\d+(?:[.,]\d+)?)';
        if (! $text || ! preg_match("/{$num}\s*[x×X*]\s*{$num}(?:\s*[x×X*]\s*{$num})?/u", $text, $m)) {
            return $dims;
        }

        $toFloat = fn (?string $v) => $v === null || $v === '' ? null : (float) str_replace(',', '.', $v);

        return [
            'height_cm' => $toFloat($m[1]),
            'width_cm'  => $toFloat($m[2]),
            'depth_cm'  => $toFloat($m[3] ?? null),
        ];
    }

    public static function formatDimensions(mixed $height, mixed $width, mixed $depth = null): ?string
    {
        if ($height === null || $width === null) {
            return null;
        }

        $parts = array_filter([$height, $width, $depth], fn ($v) => $v !== null && $v !== '');
        $clean = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        return implode(' × ', array_map($clean, $parts)).' cm';
    }
}
