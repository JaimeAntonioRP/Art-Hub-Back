<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un periodo de propiedad de la obra (§25). El propietario actual es el que no tiene fecha de fin. */
class Ownership extends Model
{
    public const ORIGIN_LABELS = [
        'artista'       => 'Artista (propietario inicial)',
        'venta'         => 'Venta',
        'transferencia' => 'Transferencia',
    ];

    protected $fillable = ['artwork_id', 'sale_id', 'owner_name', 'owner_document', 'owner_email', 'origin', 'from_date', 'to_date'];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date'   => 'date',
        ];
    }

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * El primer propietario es el artista, desde que registró la obra. Se crea la primera
     * vez que la obra cambia de manos, para que el historial empiece completo.
     */
    public static function ensureInitial(Artwork $artwork): void
    {
        if ($artwork->ownerships()->exists()) {
            return;
        }

        $artwork->ownerships()->create([
            'owner_name' => $artwork->artist?->name ?? 'Artista',
            'origin'     => 'artista',
            'from_date'  => $artwork->created_at?->toDateString(),
        ]);
    }
}
