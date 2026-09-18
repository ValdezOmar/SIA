<?php

namespace App\Filament\Resources\Compras\FacturaCompraResource\Pages;

use App\Filament\Resources\Compras\FacturaCompraResource;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Compras\FacturaCompra;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFacturaCompra extends CreateRecord
{
    protected static string $resource = FacturaCompraResource::class;

    protected array $pagoInicial = [];

    protected array $resultadoRegistro = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['codigo'] = $data['codigo'] ?? FacturaCompra::generarCodigo();
        // Los valores del formulario pueden no viajar si se conservan sus valores por defecto.
        // La regla de negocio de compras es contado en efectivo salvo elección explícita del usuario.
        $data['condicion_pago'] = ($data['condicion_pago'] ?? null) ?: 'contado';
        $data['pago_tipo'] = ($data['pago_tipo'] ?? null) ?: 'efectivo';

        if (in_array($data['condicion_pago'] ?? null, ['contado', 'parcial'], true)) {
            $this->pagoInicial = [
                'fecha_pago' => $data['pago_fecha'] ?? today()->toDateString(),
                'tipo_pago' => $data['pago_tipo'] ?? 'efectivo',
                'referencia' => $data['pago_referencia'] ?? null,
                'respaldos' => $data['pago_respaldos'] ?? [],
                'monto' => ($data['condicion_pago'] ?? null) === 'contado' ? null : ($data['pago_monto'] ?? null),
            ];
        }

        unset($data['pago_fecha'], $data['pago_tipo'], $data['pago_referencia'], $data['pago_respaldos'], $data['pago_monto']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var FacturaCompra $factura */
        $factura = $this->record->fresh();
        $factura->recalcularTotales();
        $factura->refresh();
        $factura->updateQuietly([
            'estado' => 'registrada',
            'monto_pagado' => 0,
            'saldo' => $factura->total,
            'pago_pendiente' => false,
        ]);
        $factura->refresh();

        $pago = null;
        if ($factura->condicion_pago === 'contado' && (float) $factura->saldo > 0) {
            $pago = $factura->pagos()->where('estado', 'confirmado')->latest('id')->first();
            $pago ??= $factura->registrarPago(array_merge($this->pagoInicial, [
                'monto' => $factura->saldo,
                'automatico' => true,
                'observaciones' => 'Pago automático al contado al registrar la factura.',
            ]));
        } elseif ($factura->condicion_pago === 'parcial' && ! empty($this->pagoInicial['monto'])) {
            $pago = $factura->registrarPago($this->pagoInicial);
        }

        // No ingresa stock: solo prepara la recepción para la verificación física.
        $factura->refresh();
        $recepcion = $factura->prepararRecepcionPendiente();
        $factura->refresh();
        $pago ??= $factura->pagos()->where('estado', 'confirmado')->latest('id')->first();

        $this->resultadoRegistro = [
            'factura' => $factura,
            'pago' => $pago,
            'recepcion' => $recepcion,
            'asiento_confirmado' => $pago && AsientoContable::query()
                ->where('documento_tipo', 'pago_proveedor')
                ->where('documento_id', $pago->id)
                ->where('estado', 'confirmado')
                ->exists(),
        ];
        $this->record = $factura;

        // Se envía dentro del ciclo Livewire de creación, antes de navegar al listado.
        $this->crearNotificacionRegistro()->send();
    }

    /**
     * Reanuda una creación que fue interrumpida antes de guardar los detalles.
     * Esto evita que un encabezado huérfano bloquee el segundo clic del usuario.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $incompleta = FacturaCompra::query()
            ->where('codigo', $data['codigo'] ?? null)
            ->where('estado', 'borrador')
            ->whereDoesntHave('detalles')
            ->whereDoesntHave('pagos')
            ->first();

        if ($incompleta) {
            $incompleta->fill($data);
            $incompleta->save();

            return $incompleta;
        }

        return parent::handleRecordCreation($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        // La notificación se envía en afterCreate para que no se pierda con la navegación SPA.
        return null;
    }

    private function crearNotificacionRegistro(): Notification
    {
        /** @var FacturaCompra $factura */
        $factura = $this->resultadoRegistro['factura'] ?? $this->record->fresh();
        $pago = $this->resultadoRegistro['pago'] ?? null;
        $recepcion = $this->resultadoRegistro['recepcion'] ?? null;
        $moneda = $factura->moneda ?? 'BOB';
        $monto = number_format((float) $factura->total, 2).' '.$moneda;

        $lineas = [
            'Factura '.$factura->codigo.' creada por '.$monto.'.',
            $pago
                ? 'Pago automático '.$pago->codigo.' confirmado por '.number_format((float) $pago->monto, 2).' '.$moneda.'.'
                : 'No se generó pago automático: la condición de pago mantiene saldo pendiente.',
            ($this->resultadoRegistro['asiento_confirmado'] ?? false)
                ? 'Contabilidad: asiento del pago confirmado.'
                : 'Contabilidad: no se creó asiento de pago.',
            $recepcion
                ? 'Inventario: recepción '.$recepcion->codigo.' preparada; el stock no fue ingresado.'
                : 'Inventario: no se preparó recepción.',
        ];

        return Notification::make()
            ->success()
            ->title('Factura de compra registrada')
            ->body(implode("\n", $lineas))
            ->persistent();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
