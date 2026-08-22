<?php

namespace App\Filament\Resources\Compras\ProveedorResource\Pages;

use App\Filament\Resources\Compras\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProveedors extends ListRecords
{
    protected static string $resource = ProveedorResource::class;

    public function getSubheading(): ?string
    {
        return 'Empiece aquí: registre proveedores activos antes de crear órdenes de compra.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
