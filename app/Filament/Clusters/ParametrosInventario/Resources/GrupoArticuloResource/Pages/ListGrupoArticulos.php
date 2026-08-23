<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrupoArticulos extends ListRecords
{
    protected static string $resource = GrupoArticuloResource::class;

    public function getSubheading(): ?string
    {
        return 'Organice los artículos en grupos y subgrupos para facilitar su búsqueda y reportes.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo grupo'),
        ];
    }
}
