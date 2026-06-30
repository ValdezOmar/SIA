<?php

namespace App\Filament\Resources\Inventario\UnidadMedidaResource\Pages;

use App\Filament\Resources\Inventario\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnidadMedidas extends ListRecords
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
