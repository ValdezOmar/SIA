<?php

namespace App\Filament\Clusters\Sistema\Resources\CargoResource\Pages;

use App\Filament\Clusters\Sistema\Resources\CargoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCargo extends EditRecord
{
    protected static string $resource = CargoResource::class;

    //Redirigir al index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}