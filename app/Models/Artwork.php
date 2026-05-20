<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Artwork extends Model
{
    protected $fillable = [
        'title',
        'artist_name',
        'price',
        'description',
        'image_url',
        'model_3d_url',
        'status',
    ];

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }
}
