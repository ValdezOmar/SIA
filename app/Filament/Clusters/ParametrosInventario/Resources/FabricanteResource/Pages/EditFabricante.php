<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource;

use Filament\Resources\Pages\EditRecord;

class EditFabricante extends EditRecord
{
    protected static string $resource = FabricanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}
