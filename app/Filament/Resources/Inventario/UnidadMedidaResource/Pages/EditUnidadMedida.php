<?php

namespace App\Filament\Resources\Inventario\UnidadMedidaResource\Pages;

use App\Filament\Resources\Inventario\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnidadMedida extends EditRecord
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
