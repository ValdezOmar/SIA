<?php

namespace App\Filament\Resources\Compras\PagoProveedorResource\Pages;

use App\Filament\Resources\Compras\PagoProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPagoProveedors extends ListRecords
{
    protected static string $resource = PagoProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
