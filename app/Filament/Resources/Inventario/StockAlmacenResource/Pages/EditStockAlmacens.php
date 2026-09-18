<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Pages;

use App\Filament\Resources\Inventario\StockAlmacenResource;
use App\Filament\Resources\Inventario\StockAlmacenResource\Widgets\AlmacenIndicadoresWidget;
use Filament\Resources\Pages\ViewRecord;

class EditStockAlmacens extends ViewRecord
{
    protected static string $resource = StockAlmacenResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AlmacenIndicadoresWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
