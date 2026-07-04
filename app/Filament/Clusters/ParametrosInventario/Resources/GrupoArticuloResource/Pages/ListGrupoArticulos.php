<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource;
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
