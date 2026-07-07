<?php

namespace App\Filament\Resources\Ventas\CotizacionResource\Pages;

use App\Filament\Resources\Ventas\CotizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCotizaciones extends ListRecords
{
    protected static string $resource = CotizacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
