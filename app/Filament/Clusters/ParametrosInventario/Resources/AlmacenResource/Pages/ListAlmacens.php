<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
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
