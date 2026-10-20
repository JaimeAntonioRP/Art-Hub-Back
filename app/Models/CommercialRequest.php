<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de información o intención de compra sobre una obra (§14). */
class CommercialRequest extends Model
{
    public const TYPES = ['informacion', 'intencion_compra'];

    public const TYPE_LABELS = [
        'informacion' => 'Información',
        'intencion_compra' => 'Intención de compra',
    ];

    public const STATUSES = ['nueva', 'contactado', 'en_evaluacion', 'confirmada', 'cancelada'];

    public const STATUS_NUEVA = 'nueva';

    public const STATUS_LABELS = [
        'nueva' => 'Nueva',
        'contactado' => 'Contactado',
        'en_evaluacion' => 'En evaluación',
        'confirmada' => 'Confirmada',
        'cancelada' => 'Cancelada',
    ];

    protected $fillable = ['artwork_id', 'user_id', 'name', 'email', 'phone', 'type', 'message', 'status', 'admin_notes'];

    protected $attributes = [
        'status' => self::STATUS_NUEVA,
        'type' => 'informacion',
    ];

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
