<?php

namespace App\Filament\Resources\Almacen\InventarioHistoricoResource\Pages;

use App\Filament\Resources\Almacen\InventarioHistoricoResource;
use App\Filament\Resources\Almacen\InventarioResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListInventariosHistoricos extends ListRecords
{
    protected static string $resource = InventarioHistoricoResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('volver')->label('Inventarios físicos')->url(InventarioResource::getUrl('index'))];
    }
}
