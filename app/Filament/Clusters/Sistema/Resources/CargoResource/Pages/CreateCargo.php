<?php

namespace App\Filament\Clusters\Sistema\Resources\CargoResource\Pages;

use App\Filament\Clusters\Sistema\Resources\CargoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCargo extends CreateRecord
{
    protected static string $resource = CargoResource::class;
    //Redirigir al index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}