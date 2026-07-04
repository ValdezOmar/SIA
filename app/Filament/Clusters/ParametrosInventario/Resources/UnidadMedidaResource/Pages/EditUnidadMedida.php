<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UnidadMedidaResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UnidadMedidaResource;
use Filament\Resources\Pages\EditRecord;

class EditUnidadMedida extends EditRecord
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
