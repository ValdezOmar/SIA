<?php

namespace App\Filament\Resources\Inventario\AtributoResource\Pages;

use App\Filament\Resources\Inventario\AtributoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAtributos extends ListRecords
{
    protected static string $resource = AtributoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
