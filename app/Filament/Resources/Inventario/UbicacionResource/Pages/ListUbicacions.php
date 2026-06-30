<?php

namespace App\Filament\Resources\Inventario\UbicacionResource\Pages;

use App\Filament\Resources\Inventario\UbicacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUbicacions extends ListRecords
{
    protected static string $resource = UbicacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
