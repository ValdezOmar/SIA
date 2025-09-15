<?php

namespace App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages;

use App\Filament\Clusters\Sistema\Resources\SucursalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSucursal extends EditRecord
{
    protected static string $resource = SucursalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
