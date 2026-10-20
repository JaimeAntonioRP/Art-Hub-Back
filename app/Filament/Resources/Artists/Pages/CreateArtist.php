<?php

namespace App\Filament\Resources\Artists\Pages;

use App\Filament\Resources\Artists\ArtistResource;
use App\Models\Artist;
use Filament\Resources\Pages\CreateRecord;

class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;

    /** Los perfiles que carga ArtHub (p. ej. maestros históricos) se publican aprobados. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Artist::uniqueSlug($data['name']);
        $data['status'] = Artist::STATUS_APROBADO;

        return $data;
    }
}
