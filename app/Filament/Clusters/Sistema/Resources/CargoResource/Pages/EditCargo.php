<?php

namespace App\Filament\Clusters\Sistema\Resources\CargoResource\Pages;

use App\Filament\Clusters\Sistema\Resources\CargoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCargo extends EditRecord
{
    protected static string $resource = CargoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
