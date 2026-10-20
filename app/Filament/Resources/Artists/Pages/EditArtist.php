<?php

namespace App\Filament\Resources\Artists\Pages;

use App\Filament\Resources\Artists\ArtistResource;
use App\Filament\Resources\Artists\ReviewActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditArtist extends EditRecord
{
    protected static string $resource = ArtistResource::class;

    protected function getHeaderActions(): array
    {
        return ReviewActions::all();
    }

    /** Tras aprobar u observar desde la cabecera, la insignia de estado debe reflejarlo. */
    protected function afterActionCalled(Action $action): void
    {
        parent::afterActionCalled($action);

        $this->refreshFormData(['status']);
    }
}
