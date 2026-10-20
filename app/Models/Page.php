<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Página pública del sitio armada con bloques (Inicio, Cómo funciona, Nosotros…). */
class Page extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'blocks', 'is_published'];

    protected $attributes = [
        'is_published' => true,
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
