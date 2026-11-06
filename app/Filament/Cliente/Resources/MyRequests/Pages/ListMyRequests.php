<?php

namespace App\Filament\Cliente\Resources\MyRequests\Pages;

use App\Filament\Cliente\Resources\MyRequests\MyRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListMyRequests extends ListRecords
{
    protected static string $resource = MyRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('catalogo')
                ->label('Ver la colección')
                ->url(config('app.frontend_url').'/obras'),
        ];
    }
}
