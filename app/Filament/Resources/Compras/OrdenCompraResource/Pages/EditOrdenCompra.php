<?php

namespace App\Filament\Resources\Compras\OrdenCompraResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Compras\OrdenCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrdenCompra extends EditRecord
{
    protected static string $resource = OrdenCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
