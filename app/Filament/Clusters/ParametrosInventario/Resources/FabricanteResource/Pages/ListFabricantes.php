<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFabricantes extends ListRecords
{
    protected static string $resource = FabricanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
