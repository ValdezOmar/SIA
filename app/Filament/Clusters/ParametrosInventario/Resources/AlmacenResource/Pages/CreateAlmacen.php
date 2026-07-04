<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAlmacen extends CreateRecord
{
    protected static string $resource = AlmacenResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
