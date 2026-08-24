<?php

namespace App\Filament\Resources\Ventas\FacturaResource\Pages;

use App\Filament\Resources\Ventas\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacturas extends ListRecords
{
    protected static string $resource = FacturaResource::class;

    public function getSubheading(): ?string
    {
        return 'Registre la venta final aquí cuando entregue los productos o complete el servicio. Después gestione los cobros en la propia factura.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
