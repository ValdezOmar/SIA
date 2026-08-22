<?php

namespace App\Filament\Resources\Compras\FacturaCompraResource\Pages;

use App\Filament\Resources\Compras\FacturaCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacturaCompras extends ListRecords
{
    protected static string $resource = FacturaCompraResource::class;

    public function getSubheading(): ?string
    {
        return 'Paso 4: registre la factura vinculada a la orden o recepción y revise el saldo antes de pagar.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
