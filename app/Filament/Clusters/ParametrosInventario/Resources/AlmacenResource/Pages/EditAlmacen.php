<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
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
