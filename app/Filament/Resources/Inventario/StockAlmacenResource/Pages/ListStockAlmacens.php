<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Pages;

use App\Filament\Resources\Inventario\StockAlmacenResource;
use Filament\Resources\Pages\ListRecords;

class ListStockAlmacens extends ListRecords
{
    protected static string $resource = StockAlmacenResource::class;

    public function getSubheading(): ?string
    {
        return 'Seleccione un almacén para revisar dónde se guardan los productos y consultar su stock actual.';
    }
}
