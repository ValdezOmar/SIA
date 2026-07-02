<?php

namespace App\Filament\Resources\Inventario\ListaPrecioResource\Pages;

use App\Filament\Resources\Inventario\ListaPrecioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateListaPrecio extends CreateRecord
{
    protected static string $resource = ListaPrecioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
