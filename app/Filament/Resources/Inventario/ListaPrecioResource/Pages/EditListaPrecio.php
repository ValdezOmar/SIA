<?php

namespace App\Filament\Resources\Inventario\ListaPrecioResource\Pages;

use App\Filament\Resources\Inventario\ListaPrecioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditListaPrecio extends EditRecord
{
    protected static string $resource = ListaPrecioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
