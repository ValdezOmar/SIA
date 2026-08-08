<?php

namespace App\Filament\Resources\Compras\OrdenCompraResource\Pages;

use App\Filament\Resources\Compras\OrdenCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenCompra extends EditRecord
{
    protected static string $resource = OrdenCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
