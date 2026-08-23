<?php

namespace App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages;

use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransferenciaAlmacen extends CreateRecord
{
    protected static string $resource = TransferenciaAlmacenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
