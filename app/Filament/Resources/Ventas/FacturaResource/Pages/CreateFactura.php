<?php

namespace App\Filament\Resources\Ventas\FacturaResource\Pages;

use Illuminate\Validation\ValidationException;
use App\Filament\Resources\Ventas\FacturaResource;
use App\Models\Ventas\Cotizacion;
use Filament\Resources\Pages\CreateRecord;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected ?array $pagoInicial = null;

    public function mount(): void
    {
        parent::mount();

        $cotizacionId = request()->query('cotizacion_origen_id');
        if (! $cotizacionId) {
            return;
        }

        $cotizacion = FacturaResource::cotizacionesAbiertas()
            ->with('detalles')
            ->find($cotizacionId);

        if (! $cotizacion) {
            throw ValidationException::withMessages([
                'data.cotizacion_origen_id' => 'La cotización ya no está disponible para convertir. Verifique su estado, vigencia y sucursal.',
            ]);
        }

        $detalles = $cotizacion->detalles->map(fn ($detalle) => $detalle->only([
            'articulo_id', 'lista_precio', 'codigo_articulo', 'descripcion_articulo',
            'unidad_medida', 'cantidad', 'precio_unitario', 'precio_original',
            'descuento', 'descuento_porcentaje', 'subtotal', 'tipo_impuesto',
            'tasa_impuesto', 'aplicar_iva', 'impuesto', 'total', 'observaciones',
        ]))->all();

        $this->form->fill([
            'empresa_id' => $cotizacion->empresa_id,
            'sucursal_id' => $cotizacion->sucursal_id,
            'cotizacion_origen_id' => $cotizacion->getKey(),
            'cliente_id' => $cotizacion->cliente_id,
            'vendedor_id' => $cotizacion->vendedor_id,
            'moneda' => $cotizacion->moneda,
            'tasa_cambio' => $cotizacion->tasa_cambio,
            'condicion_pago' => $cotizacion->condicion_pago === 'parcial' ? 'parcial' : 'contado',
            'pago_inicial_monto' => (float) $cotizacion->detalles->sum('total'),
            'pago_inicial_monto_sugerido' => (float) $cotizacion->detalles->sum('total'),
            'fecha_emision' => today()->toDateString(),
            'fecha_vencimiento' => $cotizacion->fecha_entrega_estimada?->toDateString() ?? today()->toDateString(),
            'estado' => 'borrador',
            'observaciones' => $cotizacion->observaciones,
            'detalles' => $detalles,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['cotizacion_origen_id'])) {
            $cotizacion = FacturaResource::cotizacionesAbiertas($data['empresa_id'] ?? null, $data['sucursal_id'] ?? null)
                ->lockForUpdate()->find($data['cotizacion_origen_id']);
            if (! $cotizacion || (int) $cotizacion->cliente_id !== (int) ($data['cliente_id'] ?? 0)) {
                throw ValidationException::withMessages([
                    'data.cotizacion_origen_id' => 'La cotización ya no está abierta o no corresponde al cliente seleccionado.',
                ]);
            }
            $pedido = $cotizacion->convertirPedido();
            $pedido->update([
                'fecha_pedido' => $data['fecha_vencimiento'] ?? $data['fecha_emision'] ?? now()->toDateString(),
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
                'fecha_pago' => $data['fecha_pago'] ?? $data['fecha_emision'] ?? now()->toDateString(),
                'tipo_pago' => $data['pago_inicial_tipo'] ?? 'efectivo',
                'monto_efectivo' => $data['pago_inicial_efectivo'] ?? null,
                'monto_qr' => $data['pago_inicial_qr'] ?? null,
                'referencia' => $data['pago_inicial_referencia'] ?? null,
                'banco' => $data['pago_inicial_banco'] ?? null,
                'numero_cheque' => $data['pago_inicial_numero_cheque'] ?? null,
            ];
        }
        unset($data['pago_inicial_monto'], $data['pago_inicial_tipo'], $data['pago_inicial_referencia'], $data['pago_inicial_banco'], $data['pago_inicial_numero_cheque']);
        unset($data['pago_inicial_efectivo'], $data['pago_inicial_qr']);

        if (($data['condicion_pago'] ?? null) === 'contado') {
            $data['fecha_vencimiento'] ??= $data['fecha_emision'] ?? now()->toDateString();
            $data['fecha_pago'] ??= $data['fecha_emision'] ?? now()->toDateString();
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
