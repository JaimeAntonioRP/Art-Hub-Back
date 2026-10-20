<?php

namespace App\Filament\Resources\Artworks\Pages;

use App\Filament\Actions\ArtworkReviewActions;
use App\Filament\Resources\Artworks\ArtworkResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ArtworkReviewActions::forAdmin(),
            ActionGroup::make([DeleteAction::make()]),
        ];
    }

    /** Tras una acción de revisión, la insignia de estado debe reflejarla. */
    protected function afterActionCalled(Action $action): void
    {
        parent::afterActionCalled($action);

        $this->refreshFormData(['status']);
    }
}
