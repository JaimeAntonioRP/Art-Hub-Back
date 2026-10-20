<?php

namespace App\Filament\Artista\Resources\Artworks\Pages;

use App\Filament\Artista\Resources\Artworks\ArtworkResource;
use App\Models\Artwork;
use Filament\Resources\Pages\CreateRecord;

class CreateArtwork extends CreateRecord
{
    protected static string $resource = ArtworkResource::class;

    /** La obra es siempre del artista que la registra y empieza como borrador. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['artist_id'] = auth()->user()->artist->id;
        $data['status'] = Artwork::STATUS_BORRADOR;

        return $data;
    }

    /** Tras crearla se sigue en la edición, donde se suben imágenes y documentos. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
