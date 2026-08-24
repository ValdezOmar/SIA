<?php

namespace App\Filament\Resources\Ventas\FacturaResource\Pages;

use App\Filament\Resources\Ventas\FacturaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

    protected ?array $pagoInicial = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['condicion_pago'] ?? null) === 'parcial') {
            $this->pagoInicial = [
                'monto' => $data['pago_inicial_monto'] ?? null,
                'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                'tipo_pago' => 'efectivo',
            ];
        }
        unset($data['pago_inicial_monto']);

        if (($data['condicion_pago'] ?? null) === 'contado') {
            $data['fecha_vencimiento'] = now()->toDateString();
            $data['fecha_pago'] = now()->toDateString();
        }

        return $data;
    }

    protected function afterCreate(): void
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
            'saldo' => $total,
            'monto_pagado' => 0,
            'monto_restante' => $total,
        ]);

        if ($this->pagoInicial) {
            $this->record->registrarPago($this->pagoInicial);
        }

    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
