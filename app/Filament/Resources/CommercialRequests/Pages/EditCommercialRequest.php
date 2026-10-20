<?php

namespace App\Filament\Resources\CommercialRequests\Pages;

use App\Filament\Resources\CommercialRequests\CommercialRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditCommercialRequest extends EditRecord
{
    protected static string $resource = CommercialRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
