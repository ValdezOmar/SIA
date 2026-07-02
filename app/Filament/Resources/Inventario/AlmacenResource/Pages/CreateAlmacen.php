<?php

namespace App\Filament\Resources\Inventario\AlmacenResource\Pages;

use App\Filament\Resources\Inventario\AlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAlmacen extends CreateRecord
{
    protected static string $resource = AlmacenResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
