<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource;
use Filament\Resources\Pages\EditRecord;

class EditUbicacion extends EditRecord
{
    protected static string $resource = UbicacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // Actions\DeleteAction::make(),
        ];
    }
}
