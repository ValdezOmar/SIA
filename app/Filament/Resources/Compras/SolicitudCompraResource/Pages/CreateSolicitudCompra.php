<?php

namespace App\Filament\Resources\Compras\SolicitudCompraResource\Pages;

use App\Filament\Resources\Compras\SolicitudCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSolicitudCompra extends CreateRecord
{
    protected static string $resource = SolicitudCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
