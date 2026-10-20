<?php

namespace App\Filament\Resources\Artists\Schemas;

use App\Models\Artist;

/** Color de la insignia de cada estado del perfil, igual en tabla y formulario. */
final class ArtistStatus
{
    public static function color(string $status): string
    {
        return match ($status) {
            Artist::STATUS_APROBADO => 'success',
            Artist::STATUS_OBSERVADO => 'warning',
            Artist::STATUS_RECHAZADO => 'danger',
            Artist::STATUS_PENDIENTE => 'info',
            default => 'gray',
        };
    }
}
