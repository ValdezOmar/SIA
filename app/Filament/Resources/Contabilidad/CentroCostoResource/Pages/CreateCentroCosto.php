<?php

namespace App\Filament\Resources\Contabilidad\CentroCostoResource\Pages;

use App\Filament\Resources\Contabilidad\CentroCostoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCentroCosto extends CreateRecord
{
    protected static string $resource = CentroCostoResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
