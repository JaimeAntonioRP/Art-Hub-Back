<?php

namespace App\Filament\Artista\Resources\Artworks\Pages;

use App\Filament\Actions\ArtworkReviewActions;
use App\Filament\Artista\Resources\Artworks\ArtworkResource;
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
            ArtworkReviewActions::submitForReview(),
            ActionGroup::make([DeleteAction::make()]),
        ];
    }

    /** El artista no cambia de dueño ni de estado desde el formulario. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['artist_id'], $data['status']);

        return $data;
    }

    /** Tras enviarla a revisión ya no puede editarla: pasa a la vista de solo lectura. */
    protected function afterActionCalled(Action $action): void
    {
        parent::afterActionCalled($action);

        if ($action->getName() === 'submitForReview' && ! auth()->user()->can('update', $this->getRecord()->refresh())) {
            $this->redirect(ArtworkResource::getUrl('view', ['record' => $this->getRecord()]));
        }
    }
}
