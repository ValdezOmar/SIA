<?php

namespace App\Filament\Resources\Compras\RecepcionResource\Pages;

use App\Filament\Resources\Compras\RecepcionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRecepcion extends CreateRecord
{
    protected static string $resource = RecepcionResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
