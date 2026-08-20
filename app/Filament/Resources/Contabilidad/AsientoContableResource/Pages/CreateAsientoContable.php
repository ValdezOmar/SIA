<?php

namespace App\Filament\Resources\Contabilidad\AsientoContableResource\Pages;

use App\Filament\Resources\Contabilidad\AsientoContableResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAsientoContable extends CreateRecord
{
    protected static string $resource = AsientoContableResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
