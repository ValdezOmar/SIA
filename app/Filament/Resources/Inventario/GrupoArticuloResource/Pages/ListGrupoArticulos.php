<?php

namespace App\Filament\Resources\Inventario\GrupoArticuloResource\Pages;

use App\Filament\Resources\Inventario\GrupoArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrupoArticulos extends ListRecords
{
    protected static string $resource = GrupoArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
