<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evento del historial operativo de una obra (§11). Solo se agregan, nunca se editan. */
class ArtworkEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['artwork_id', 'actor_id', 'type', 'payload'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
