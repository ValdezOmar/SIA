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
        if (! empty($data['cotizacion_origen_id'])) {
            $cotizacion = FacturaResource::cotizacionesAbiertas($data['empresa_id'] ?? null, $data['sucursal_id'] ?? null)
                ->lockForUpdate()->find($data['cotizacion_origen_id']);
            if (! $cotizacion || (int) $cotizacion->cliente_id !== (int) ($data['cliente_id'] ?? 0)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.cotizacion_origen_id' => 'La cotización ya no está abierta o no corresponde al cliente seleccionado.',
                ]);
            }
            $pedido = $cotizacion->convertirPedido();
            $pedido->update([
                'fecha_pedido' => $data['fecha_vencimiento'] ?? now()->toDateString(),
                'condicion_pago' => $data['condicion_pago'],
                'vendedor_id' => $data['vendedor_id'] ?? $cotizacion->vendedor_id,
            ]);
            $data['pedido_id'] = $pedido->id;
            $data['numero_pedido'] = $pedido->codigo;
        }
        unset($data['cotizacion_origen_id']);

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
