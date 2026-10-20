<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Filament\Resources\Artworks\ArtworkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtwork extends CreateRecord
{
    protected static string $resource = ArtworkResource::class;

    /** Tras crearla se sigue en la edición, donde se suben imágenes y documentos. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
