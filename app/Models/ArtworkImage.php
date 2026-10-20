<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArtworkImage extends Model
{
    public const KINDS = ['principal', 'adicional', 'posterior', 'detalle', 'firma'];

    protected $fillable = ['artwork_id', 'kind', 'path', 'position', 'is_primary'];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'position'   => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    /** Las imágenes antiguas guardan una URL o ruta pública; las nuevas, una ruta del disco. */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => preg_match('#^(https?://|/)#', $this->path)
            ? $this->path
            : Storage::disk('public')->url($this->path));
    }
}
