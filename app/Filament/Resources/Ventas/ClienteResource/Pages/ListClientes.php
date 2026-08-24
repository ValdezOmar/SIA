<?php

namespace App\Filament\Resources\Ventas\ClienteResource\Pages;

use App\Filament\Resources\Ventas\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientes extends ListRecords
{
    protected static string $resource = ClienteResource::class;

    public function getSubheading(): ?string
    {
        return 'Primero registre al cliente. Después podrá crear sus cotizaciones, pedidos y facturas desde el módulo Ventas.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
