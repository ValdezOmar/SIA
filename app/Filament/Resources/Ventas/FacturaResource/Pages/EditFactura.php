<?php

namespace App\Filament\Resources\Ventas\FacturaResource\Pages;

use App\Filament\Resources\Ventas\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFactura extends EditRecord
{
    protected static string $resource = FacturaResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['condicion_pago'] ?? null) === 'contado') {
            $data['fecha_vencimiento'] = now()->toDateString();
            $data['fecha_pago'] = now()->toDateString();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refresh();

        $subtotal = (float) $this->record->detalles()->sum('subtotal');
        $descuento = (float) $this->record->detalles()->sum('descuento');
        $impuesto = (float) $this->record->detalles()->sum('impuesto');
        $total = (float) $this->record->detalles()->sum('total');

        $this->record->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuesto' => $impuesto,
            'total' => $total,
            'saldo' => max(0, $total - ($this->record->monto_pagado ?? 0)),
            'monto_restante' => max(0, $total - ($this->record->monto_pagado ?? 0)),
        ]);

        if (($this->record->condicion_pago ?? null) === 'contado') {
            $this->record->crearPagoAutomaticoSiEsContado();
        }

        $this->record->procesarVentaAutomatica();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
