<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    public const STATUS_VIGENTE = 'vigente';
    public const STATUS_ANULADO = 'anulado';
    public const STATUS_REEMPLAZADO = 'reemplazado';

    protected $fillable = [
        'artwork_id',
        'verification_token',
        'status',
    ];

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }
}
