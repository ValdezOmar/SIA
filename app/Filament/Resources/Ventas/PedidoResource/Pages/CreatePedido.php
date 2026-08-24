<?php

namespace App\Filament\Resources\Ventas\PedidoResource\Pages;

use App\Filament\Resources\Ventas\PedidoResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use RuntimeException;

class CreatePedido extends CreateRecord
{
    protected static string $resource = PedidoResource::class;

    protected function afterCreate(): void
    {
        try {
            $this->record->reservarInventario();
        } catch (RuntimeException $exception) {
            $this->record->update([
                'estado' => 'pendiente',
                'observaciones' => trim(($this->record->observaciones ? $this->record->observaciones."\n" : '').'Reserva pendiente: '.$exception->getMessage()),
            ]);

            Notification::make()
                ->title('Pedido guardado sin reserva de stock')
                ->body($exception->getMessage().' Revise el inventario y use “Reservar stock” cuando haya disponibilidad.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
