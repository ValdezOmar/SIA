<?php

namespace App\Filament\Resources\Compras\OrdenCompraResource\Pages;

use App\Filament\Resources\Compras\OrdenCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrdenCompra extends CreateRecord
{
    protected static string $resource = OrdenCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
