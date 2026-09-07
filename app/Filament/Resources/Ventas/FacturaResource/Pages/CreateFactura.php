<?php

namespace App\Filament\Resources\Ventas\FacturaResource\Pages;

use App\Filament\Resources\Ventas\FacturaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected ?array $pagoInicial = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (in_array($data['condicion_pago'] ?? null, ['contado', 'parcial'], true)) {
            $this->pagoInicial = [
                'monto' => ($data['condicion_pago'] ?? null) === 'parcial' ? ($data['pago_inicial_monto'] ?? null) : null,
                'fecha_pago' => $data['fecha_pago'] ?? now()->toDateString(),
                'tipo_pago' => $data['pago_inicial_tipo'] ?? 'efectivo',
                'referencia' => $data['pago_inicial_referencia'] ?? null,
                'banco' => $data['pago_inicial_banco'] ?? null,
                'numero_cheque' => $data['pago_inicial_numero_cheque'] ?? null,
            ];
        }
        unset($data['pago_inicial_monto'], $data['pago_inicial_tipo'], $data['pago_inicial_referencia'], $data['pago_inicial_banco'], $data['pago_inicial_numero_cheque']);

        if (($data['condicion_pago'] ?? null) === 'contado') {
            $data['fecha_vencimiento'] ??= now()->toDateString();
            $data['fecha_pago'] ??= now()->toDateString();
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

        if (($this->record->condicion_pago ?? null) === 'contado') {
            $this->record->crearPagoAutomaticoSiEsContado($this->pagoInicial ?? []);
        } elseif ($this->pagoInicial) {
            $this->record->registrarPago($this->pagoInicial);
        }

    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
