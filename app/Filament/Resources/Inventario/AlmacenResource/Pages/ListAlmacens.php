<?php

namespace App\Filament\Resources\Inventario\AlmacenResource\Pages;

use App\Filament\Resources\Inventario\AlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAlmacens extends ListRecords
{
    protected static string $resource = AlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
