<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ArtistProfileReviewed;
use App\Support\Disks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Artist extends Model
{
    /** Estados del perfil (§6). */
    public const STATUSES = ['pendiente', 'observado', 'aprobado', 'rechazado', 'inactivo'];
    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_OBSERVADO = 'observado';
    public const STATUS_APROBADO = 'aprobado';
    public const STATUS_RECHAZADO = 'rechazado';
    public const STATUS_INACTIVO = 'inactivo';

    public const STATUS_LABELS = [
        'pendiente' => 'Pendiente de revisión',
        'observado' => 'Observado',
        'aprobado'  => 'Aprobado',
        'rechazado' => 'Rechazado',
        'inactivo'  => 'Inactivo',
    ];

    /** Estado actual => estados a los que se puede pasar. */
    private const TRANSITIONS = [
        'pendiente' => ['aprobado', 'observado', 'rechazado'],
        'observado' => ['pendiente', 'aprobado', 'rechazado'],
        'rechazado' => ['pendiente'],
        'aprobado'  => ['inactivo', 'observado'],
        'inactivo'  => ['aprobado'],
    ];

    /** Estados en los que el administrador debe dejar un motivo. */
    private const NEEDS_NOTE = ['observado', 'rechazado'];

    protected $fillable = [
        'user_id',
        'name',
        'artistic_name',
        'slug',
        'origin',
        'birth_year',
        'death_year',
        'specialty',
        'techniques',
        'bio',
        'career',
        'awards',
        'contact_email',
        'contact_phone',
        'social_links',
        'profile_image_url',
        'featured',
        'status',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDIENTE,
    ];

    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return [
            'featured'     => 'boolean',
            'birth_year'   => 'integer',
            'death_year'   => 'integer',
            'techniques'   => 'array',
            'social_links' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class);
    }

    public function reviewNotes(): MorphMany
    {
        return $this->morphMany(ReviewNote::class, 'reviewable');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APROBADO);
    }

    /** Las fotos antiguas guardan una URL o ruta pública; las subidas desde el panel, una ruta del disco. */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => Disks::mediaUrl($this->profile_image_url));
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Cambia el estado del perfil, guarda la observación y avisa al artista.
     * $by es quien revisa (null cuando el propio artista envía a revisión).
     */
    public function transitionTo(string $status, ?User $by = null, ?string $note = null): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new InvalidArgumentException("No se puede pasar de {$this->status} a {$status}.");
        }

        $note = trim((string) $note);
        if (in_array($status, self::NEEDS_NOTE, true) && $note === '') {
            throw new InvalidArgumentException('Indica el motivo de la observación o el rechazo.');
        }

        DB::transaction(function () use ($status, $by, $note) {
            $this->update(['status' => $status]);

            if ($note !== '') {
                $this->reviewNotes()->create(['author_id' => $by?->id, 'body' => $note]);
            }
            if ($status === self::STATUS_PENDIENTE) {
                $this->reviewNotes()->whereNull('resolved_at')->update(['resolved_at' => now()]);
            }
        });

        // Avisar al artista solo de las decisiones del administrador.
        if ($by && $this->user) {
            $this->user->notify(new ArtistProfileReviewed($this, $note ?: null));
        }
    }

    public static function uniqueSlug(string $name): string
    {
        $slug = $base = Str::slug($name) ?: 'artista';
        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
