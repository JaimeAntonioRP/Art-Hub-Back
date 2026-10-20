<?php

namespace App\Filament\Artista\Resources\Artworks\Pages;

use App\Filament\Artista\Resources\Artworks\ArtworkResource;
use Filament\Resources\Pages\ViewRecord;

/** Obra en revisión o publicada: el artista la ve, pero no la edita (§10). */
class ViewArtwork extends ViewRecord
{
    protected static string $resource = ArtworkResource::class;
}
