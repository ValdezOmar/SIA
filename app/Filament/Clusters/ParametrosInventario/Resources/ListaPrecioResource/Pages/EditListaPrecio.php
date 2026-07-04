<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource;

use Filament\Resources\Pages\EditRecord;

class EditListaPrecio extends EditRecord
{
    protected static string $resource = ListaPrecioResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}
