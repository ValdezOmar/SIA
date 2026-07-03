<?php

namespace App\Filament\Resources\Compras\ProveedorResource\Pages;

use App\Filament\Resources\Compras\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProveedor extends EditRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
}
