<?php

namespace App\Filament\Resources\Compras\FacturaCompraResource\Pages;

use App\Filament\Resources\Compras\FacturaCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFacturaCompra extends CreateRecord
{
    protected static string $resource = FacturaCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
