<?php

namespace App\Forms\Components;

use App\Support\CalculoDetalle;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;

class CalculoRepeater extends Repeater
{
    protected ?string $tipoCalculo = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hintAction(Action::make('calcular')
            ->label('Calcular totales')->icon('heroicon-o-calculator')
            ->action(fn (CalculoRepeater $component) => $component->calcular())
            ->successRedirectUrl('')
            ->visible(fn (CalculoRepeater $component) => $component->tipoCalculo !== null));
        $this->beforeStateDehydrated(fn (CalculoRepeater $component) => $component->calcular(), shouldUpdateValidatedStateAfter: true);
    }

    public function calculo(?string $tipo): static
    {
        $this->tipoCalculo = $tipo;
        if ($tipo) {
            $this->helperText($tipo === 'venta'
                ? 'Cantidades, precios y descuentos actualizan los importes mientras escribe, sin esperar al servidor. Al guardar se validan nuevamente.'
                : 'Escriba sin esperar. Pulse «Calcular totales» para actualizar los importes. Al guardar se recalculan nuevamente.');
        }

        return $this;
    }

    // Nunca propagar actualizaciones por pulsación a todos los campos del repetidor.
    public function live(bool $onBlur = false, int|string|null $debounce = null, bool|Closure|null $condition = true): static
    {
        return parent::live(condition: false);
    }

    public function calcular(): void
    {
        $filas = $this->getState() ?? [];
        foreach ($filas as $key => $fila) {
            if (is_array($fila)) {
                $filas[$key] = CalculoDetalle::calcular($fila, $this->tipoCalculo, $this->getStatePath().'.'.$key);
            }
        }
        $this->state($filas);

        if (in_array($this->tipoCalculo, ['venta', 'compra', 'solicitud', 'contabilidad'], true)) {
            $totales = CalculoDetalle::totales($filas, $this->tipoCalculo);
            if ($this->tipoCalculo === 'venta') {
                $totales['total'] += (float) ($this->getGetCallback()('costo_envio') ?? 0);
            }
            foreach ($totales as $campo => $valor) {
                $this->getSetCallback()($campo, $valor);
            }
        }
    }

    public function saveRelationships(): void
    {
        parent::saveRelationships();
        $record = $this->getRecord();
        if (! $record?->exists || ! $this->hasRelationship()
            || ($this->isDisabled() && ! $this->shouldSaveRelationshipsWhenDisabled())
            || ($this->isHidden() && ! $this->shouldSaveRelationshipsWhenHidden())
            || ! in_array($this->tipoCalculo, ['venta', 'compra', 'solicitud', 'contabilidad'], true)) {
            return;
        }

        // Calcular después de insertar, editar y eliminar todas las filas.
        $totales = CalculoDetalle::totales($this->getRelationship()->get(), $this->tipoCalculo);
        if ($record instanceof \App\Models\Ventas\Pedido) {
            $totales['total'] += (float) ($record->costo_envio ?? 0);
        }
        if ($record instanceof \App\Models\Ventas\Factura || $record instanceof \App\Models\Compras\FacturaCompra) {
            $totales['saldo'] = max(0, $totales['total'] - (float) ($record->monto_pagado ?? 0));
            if ($record instanceof \App\Models\Ventas\Factura) {
                $totales['monto_restante'] = $totales['saldo'];
            }
        }
        $record->forceFill($totales)->saveQuietly();
        $record->unsetRelation($this->getRelationshipName());
    }

    public function mutateRelationshipDataBeforeCreate(array $data): ?array
    {
        $data = parent::mutateRelationshipDataBeforeCreate($data);

        return $data === null ? null : $this->normalizarParaGuardar($data);
    }

    public function mutateRelationshipDataBeforeSave(array $data, Model $record): ?array
    {
        $data = parent::mutateRelationshipDataBeforeSave($data, $record);

        return $data === null ? null : $this->normalizarParaGuardar($data);
    }

    private function normalizarParaGuardar(array $data): array
    {
        $data = CalculoDetalle::calcular($data, $this->tipoCalculo, $this->getStatePath());
        unset($data['_descuento_tipo']);

        return $data;
    }
}
