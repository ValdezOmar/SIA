<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\Pages;

use App\Filament\Resources\Inventario\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticulos extends ListRecords
{
    protected static string $resource = ArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
