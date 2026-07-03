<?php

namespace App\Filament\Resources\Inventario\AlmacenResource\Pages;

use App\Filament\Resources\Inventario\AlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlmacen extends EditRecord
{
    protected static string $resource = AlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}
