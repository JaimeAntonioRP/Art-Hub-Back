<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'origin',
        'birth_year',
        'death_year',
        'specialty',
        'bio',
        'profile_image_url',
        'featured',
    ];

    protected function casts(): array
    {
        return [
            'featured'   => 'boolean',
            'birth_year' => 'integer',
            'death_year' => 'integer',
        ];
    }

    public function artworks(): HasMany
    {
        return $this->hasMany(Artwork::class, 'artist_name', 'name');
    }
}
