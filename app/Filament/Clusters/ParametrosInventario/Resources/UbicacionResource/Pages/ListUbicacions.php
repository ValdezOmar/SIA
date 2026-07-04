<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource;
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
