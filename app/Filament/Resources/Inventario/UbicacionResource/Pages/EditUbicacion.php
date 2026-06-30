<?php

namespace App\Filament\Resources\Inventario\UbicacionResource\Pages;

use App\Filament\Resources\Inventario\UbicacionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUbicacion extends EditRecord
{
    protected static string $resource = UbicacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
