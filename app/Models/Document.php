<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Documento del expediente de una obra (§9). Sus archivos viven en DocumentVersion. */
class Document extends Model
{
    public const CATEGORIES = ['declaracion', 'certificado', 'procedencia', 'informe', 'peritaje', 'permiso', 'historico', 'otro'];
    public const VISIBILITIES = ['privado', 'publico'];

    protected $fillable = ['artwork_id', 'category', 'title', 'description', 'visibility', 'is_relevant', 'created_by'];

    protected function casts(): array
    {
        return [
            'is_relevant' => 'boolean',
        ];
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version');
    }
}
