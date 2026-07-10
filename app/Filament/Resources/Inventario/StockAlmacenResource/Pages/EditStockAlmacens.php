<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Pages;

use App\Filament\Resources\Inventario\StockAlmacenResource;
use Filament\Resources\Pages\EditRecord;

class EditStockAlmacens extends EditRecord
{
    protected static string $resource = StockAlmacenResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}