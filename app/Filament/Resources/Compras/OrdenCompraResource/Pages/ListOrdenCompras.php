<?php

namespace App\Filament\Resources\Compras\OrdenCompraResource\Pages;

use App\Filament\Resources\Compras\OrdenCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrdenCompras extends ListRecords
{
    protected static string $resource = OrdenCompraResource::class;

    public function getSubheading(): ?string
    {
        return 'Paso 2: cree la orden desde una solicitud aprobada, envíela al proveedor y confírmela antes de recibir mercadería.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
