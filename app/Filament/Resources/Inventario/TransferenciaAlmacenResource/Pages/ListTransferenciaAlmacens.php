<?php

namespace App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages;

use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransferenciaAlmacens extends ListRecords
{
    protected static string $resource = TransferenciaAlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nuevo traspaso')];
    }
}
