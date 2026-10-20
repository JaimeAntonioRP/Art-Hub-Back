<?php

namespace App\Filament\Resources\CommercialRequests\Pages;

use App\Filament\Resources\CommercialRequests\CommercialRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCommercialRequests extends ListRecords
{
    protected static string $resource = CommercialRequestResource::class;
}
