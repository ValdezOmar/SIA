<?php

namespace App\Filament\Resources\Compras\FacturaCompraResource\Pages;

use App\Filament\Resources\Compras\FacturaCompraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFacturaCompra extends CreateRecord
{
    protected static string $resource = FacturaCompraResource::class;

    protected array $pagoInicial = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (in_array($data['estado'] ?? null, ['pagada', 'parcial'], true)) {
            $this->pagoInicial = [
                'fecha_pago' => $data['pago_fecha'] ?? today()->toDateString(),
                'tipo_pago' => $data['pago_tipo'] ?? 'transferencia',
                'referencia' => $data['pago_referencia'] ?? null,
                'respaldos' => $data['pago_respaldos'] ?? [],
                'monto' => ($data['estado'] ?? null) === 'pagada' ? null : ($data['pago_monto'] ?? null),
            ];
        }

        unset($data['pago_fecha'], $data['pago_tipo'], $data['pago_referencia'], $data['pago_respaldos'], $data['pago_monto']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (empty($this->pagoInicial)) {
            return;
        }

        $this->record->recalcularTotales();
        $this->record->updateQuietly([
            'estado' => 'registrada',
            'monto_pagado' => 0,
            'saldo' => $this->record->total,
            'pago_pendiente' => false,
        ]);
        $this->record->registrarPago(array_merge($this->pagoInicial, [
            'monto' => $this->pagoInicial['monto'] ?? $this->record->saldo,
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
