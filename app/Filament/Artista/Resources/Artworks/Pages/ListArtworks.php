<?php

namespace App\Filament\Artista\Resources\Artworks\Pages;

use App\Filament\Artista\Resources\Artworks\ArtworkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArtworks extends ListRecords
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva obra'),
        ];
    }
}
