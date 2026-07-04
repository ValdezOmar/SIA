<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\UnidadMedidaResource\Pages;

use App\Filament\Clusters\ParametrosInventario\Resources\UnidadMedidaResource;

use Filament\Resources\Pages\CreateRecord;

class CreateUnidadMedida extends CreateRecord
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
