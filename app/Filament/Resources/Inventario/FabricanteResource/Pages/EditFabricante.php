<?php

namespace App\Filament\Resources\Inventario\FabricanteResource\Pages;

use App\Filament\Resources\Inventario\FabricanteResource;
use Filament\Actions;
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
