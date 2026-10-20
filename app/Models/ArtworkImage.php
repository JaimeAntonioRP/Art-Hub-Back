<?php

namespace App\Models;

use App\Support\Disks;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Imagen de la galería de una obra (§8). Hay una sola principal por obra. */
class ArtworkImage extends Model
{
    public const KINDS = ['principal', 'adicional', 'posterior', 'detalle', 'firma'];

    public const KIND_LABELS = [
        'principal' => 'Principal',
        'adicional' => 'Adicional',
        'posterior' => 'Parte posterior',
        'detalle'   => 'Detalle',
        'firma'     => 'Firma',
    ];

    protected $fillable = ['artwork_id', 'kind', 'path', 'position', 'is_primary'];

    protected $appends = ['url'];

    protected $attributes = [
        'kind' => 'adicional',
        'is_primary' => false,
    ];

    protected function casts(): array
    {
        return [
            'position'   => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ArtworkImage $image) {
            $image->position ??= (int) static::where('artwork_id', $image->artwork_id)->max('position') + 1;
        });

        // "saving" corre antes que "creating": aquí se decide la principal y su tipo.
        static::saving(function (ArtworkImage $image) {
            if (! $image->exists && ! static::where('artwork_id', $image->artwork_id)->exists()) {
                $image->is_primary = true; // la primera imagen de la obra es la principal
            }

            if ($image->is_primary) {
                $image->kind = 'principal';
            } elseif ($image->kind === 'principal') {
                $image->kind = 'adicional';
            }
        });

        static::saved(function (ArtworkImage $image) {
            if ($image->is_primary && ($image->wasRecentlyCreated || $image->wasChanged('is_primary'))) {
                static::where('artwork_id', $image->artwork_id)
                    ->whereKeyNot($image->getKey())
                    ->where('is_primary', true)
                    ->get()
                    ->each(fn (ArtworkImage $other) => $other->update(['is_primary' => false]));
            }
        });

        static::deleted(function (ArtworkImage $image) {
            if ($image->isStoredFile()) {
                Disks::media()->delete($image->path);
            }
            if ($image->is_primary) {
                static::where('artwork_id', $image->artwork_id)->orderBy('position')->first()
                    ?->update(['is_primary' => true]);
            }
        });
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function makePrimary(): void
    {
        $this->update(['is_primary' => true]);
    }

    /** Las imágenes antiguas son URLs o rutas del sitio; solo las subidas viven en el disco. */
    public function isStoredFile(): bool
    {
        return ! preg_match('#^(https?://|/)#', $this->path);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => Disks::mediaUrl($this->path));
    }
}
