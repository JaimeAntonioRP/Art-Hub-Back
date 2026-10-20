<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Artist extends Model
{
    /** Estados del perfil (§6). */
    public const STATUSES = ['pendiente', 'observado', 'aprobado', 'rechazado', 'inactivo'];
    public const STATUS_APROBADO = 'aprobado';

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
}
