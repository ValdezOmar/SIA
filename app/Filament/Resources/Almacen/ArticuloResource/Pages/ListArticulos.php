<?php

namespace App\Filament\Resources\Almacen\ArticuloResource\Pages;

use App\Filament\Resources\Almacen\ArticuloResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListArticulos extends ListRecords
{
    protected static string $resource = ArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [          
        ];
    }
}