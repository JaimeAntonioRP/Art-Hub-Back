<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de información o intención de compra sobre una obra (§14). */
class CommercialRequest extends Model
{
    public const TYPES = ['informacion', 'intencion_compra'];
    public const STATUSES = ['nueva', 'contactado', 'en_evaluacion', 'confirmada', 'cancelada'];

    protected $fillable = ['artwork_id', 'user_id', 'name', 'email', 'phone', 'type', 'message', 'status', 'admin_notes'];

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
