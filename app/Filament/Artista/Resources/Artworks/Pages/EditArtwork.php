<?php

namespace App\Filament\Artista\Resources\Artworks\Pages;

use App\Filament\Artista\Resources\Artworks\ArtworkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** El artista no cambia de dueño ni de estado desde el formulario. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['artist_id'], $data['status']);

        return $data;
    }
}
