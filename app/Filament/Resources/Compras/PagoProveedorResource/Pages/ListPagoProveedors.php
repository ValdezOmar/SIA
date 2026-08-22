<?php

namespace App\Filament\Resources\Compras\PagoProveedorResource\Pages;

use App\Filament\Resources\Compras\PagoProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPagoProveedors extends ListRecords
{
    protected static string $resource = PagoProveedorResource::class;

    public function getSubheading(): ?string
    {
        return 'Paso 5: registre y confirme pagos únicamente contra facturas pendientes; el saldo se actualiza automáticamente.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
