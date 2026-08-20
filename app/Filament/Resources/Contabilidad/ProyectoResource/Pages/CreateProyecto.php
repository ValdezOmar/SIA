<?php

namespace App\Filament\Resources\Contabilidad\ProyectoResource\Pages;

use App\Filament\Resources\Contabilidad\ProyectoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProyecto extends CreateRecord
{
    protected static string $resource = ProyectoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
