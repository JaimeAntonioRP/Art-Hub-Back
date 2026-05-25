<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'artwork_id',
        'biometric_hash',
        'match_percentage',
        'blockchain_tx_id',
        'contract_address',
        'verification_token',
        'status',
    ];

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }
}
