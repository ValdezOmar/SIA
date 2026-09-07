<?php

namespace App\Filament\Resources\Almacen\InventarioResource\Pages;

use App\Filament\Resources\Almacen\InventarioHistoricoResource;
use App\Filament\Resources\Almacen\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Programar inventario'),
            Actions\Action::make('historico')->label('Registros del módulo anterior')->icon('heroicon-o-archive-box')
                ->url(fn () => InventarioHistoricoResource::getUrl('index')),
        ];
    }
}
