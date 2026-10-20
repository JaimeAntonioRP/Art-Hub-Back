<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Artwork extends Model
{
    /** Estado editorial (§10). */
    public const STATUSES = ['borrador', 'en_revision', 'observado', 'rechazado', 'aprobado', 'publicado', 'despublicado'];
    public const STATUS_PUBLICADO = 'publicado';

    /** Disponibilidad comercial, independiente del estado editorial. */
    public const AVAILABILITIES = ['disponible', 'reservada', 'vendida', 'no_venta'];

    public const CURRENCIES = ['PEN', 'USD'];

    public const STATUS_BORRADOR = 'borrador';

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

    protected $with = ['artist:id,name,slug,origin', 'images'];

    protected $appends = ['artist_name', 'image_url', 'dimensions'];

    /** URL de imagen recibida por el campo de compatibilidad; se guarda como imagen principal. */
    private ?string $pendingImageUrl = null;

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
                $artwork->recordEvent('estado_cambiado', [
                    'de' => $artwork->getOriginal('status'),
                    'a'  => $artwork->status,
                ]);
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

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLICADO);
    }

    public function primaryImage(): ?ArtworkImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function recordEvent(string $type, ?array $payload = null): void
    {
        $this->history()->create([
            'actor_id' => auth()->id(),
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
