<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource;

use Filament\Resources\Pages\CreateRecord;

class CreateUbicacion extends CreateRecord
{
    protected static string $resource = UbicacionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
