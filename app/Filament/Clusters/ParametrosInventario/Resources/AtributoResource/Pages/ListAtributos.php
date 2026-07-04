<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource;
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
