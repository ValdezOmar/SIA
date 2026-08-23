<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\Pages;

use App\Filament\Resources\Inventario\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticulos extends ListRecords
{
    protected static string $resource = ArticuloResource::class;

    public function getSubheading(): ?string
    {
        return 'Registre cada producto y luego configure precios, proveedores, unidades y controles de lote o serie cuando corresponda.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
