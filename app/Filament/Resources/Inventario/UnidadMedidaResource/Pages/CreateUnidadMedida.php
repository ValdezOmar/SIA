<?php

namespace App\Filament\Resources\Inventario\UnidadMedidaResource\Pages;

use App\Filament\Resources\Inventario\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnidadMedida extends CreateRecord
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
