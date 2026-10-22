<?php

namespace App\Filament\Resources\CommercialRequests\Pages;

use App\Filament\Resources\CommercialRequests\CommercialRequestResource;
use App\Filament\Schemas\SaleFields;
use App\Models\Artwork;
use App\Models\CommercialRequest;
use Filament\Resources\Pages\EditRecord;

class EditCommercialRequest extends EditRecord
{
    protected static string $resource = CommercialRequestResource::class;

    /** Una solicitud que termina en acuerdo se registra aquí mismo, con los datos del interesado (§26). */
    protected function getHeaderActions(): array
    {
        /** @var CommercialRequest $request */
        $request = $this->getRecord();

        return [
            SaleFields::registerAction('registerSale', fn (): Artwork => $request->artwork()->firstOrFail(), fn () => $request)
                ->after(fn () => $this->refreshFormData(['status'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
