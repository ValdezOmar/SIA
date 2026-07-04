<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource;

use Filament\Resources\Pages\CreateRecord;

class CreateListaPrecio extends CreateRecord
{
    protected static string $resource = ListaPrecioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
