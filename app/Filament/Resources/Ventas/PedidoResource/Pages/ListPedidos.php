<?php

namespace App\Filament\Resources\Ventas\PedidoResource\Pages;

use App\Filament\Resources\Ventas\PedidoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidoResource::class;

    public function getSubheading(): ?string
    {
        return 'Cree un pedido cuando el cliente confirme su compra. Úselo para preparar y controlar la entrega antes de facturar.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
