<?php

namespace App\Forms\Components;

use App\Models\Inventario\Articulo;
use App\Support\CalculoDetalle;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
            ->visible(fn (CalculoRepeater $component) => $component->tipoCalculo !== null));
        $this->beforeStateDehydrated(fn (CalculoRepeater $component) => $component->calcular());
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

    public function getChildComponents(): array
    {
        $components = parent::getChildComponents();
        if ($this->tipoCalculo === 'venta') {
            $components[] = Hidden::make('_descuento_tipo')->default('importe')->dehydrated(false);
        }

        return $components;
    }

    public function getChildComponentContainers(bool $withHidden = false): array
    {
        $containers = parent::getChildComponentContainers($withHidden);
        foreach ($containers as $container) {
            foreach ($container->getFlatFields(withHidden: true) as $field) {
                if ($field instanceof TextInput || $field instanceof Textarea || ($this->tipoCalculo && $field instanceof Toggle)) {
                    $field->live(condition: false);
                    if ($this->tipoCalculo && ($field instanceof Toggle || ($field instanceof TextInput && $field->isNumeric()))) {
                        $field->clearAfterStateUpdatedHooks();
                    }
                }

                if ($this->tipoCalculo === 'venta' && $field instanceof TextInput && in_array($field->getName(), ['descuento', 'descuento_porcentaje'], true)) {
                    // Se valida el descuento efectivo al calcular, no un importe anterior.
                    if ($field->getName() === 'descuento') {
                        $field->maxValue(null);
                    }
                }

                if ($this->tipoCalculo === 'venta' && $field instanceof TextInput && in_array($field->getName(), ['cantidad', 'precio_unitario', 'descuento', 'descuento_porcentaje'], true)) {
                    $field->extraInputAttributes([
                        'x-on:input' => 'window.siaVentasImportes.actualizar($wire, '.json_encode($container->getStatePath()).', '.json_encode($field->getName()).', $event.target.value)',
                    ], merge: true);
                }
                if ($this->tipoCalculo === 'venta' && $field instanceof Select && in_array($field->getName(), ['articulo_id', 'lista_precio'], true)) {
                    $field->live()->clearAfterStateUpdatedHooks()
                        ->afterStateUpdated(fn (Select $component) => $this->seleccionarPrecio($component));
                    if ($field->getName() === 'lista_precio') {
                        // Al cambiar artículo se recrea el selector dependiente y se cargan sus opciones.
                        $field->native(false)->searchable()->preload();
                    }
                }
                if ($this->tipoCalculo === 'venta' && $field instanceof Toggle && $field->getName() === 'aplicar_iva') {
                    $field->extraAlpineAttributes([
                        'x-init' => '$watch("state", value => window.siaVentasImportes.actualizar($wire, '.json_encode($container->getStatePath()).', "aplicar_iva", value))',
                    ], merge: true);
                }
            }
        }

        return $containers;
    }

    private function seleccionarPrecio(Select $component): void
    {
        $get = $component->getGetCallback();
        $set = $component->getSetCallback();
        $articulo = Articulo::find($get('articulo_id'));
        $precios = $articulo?->getPreciosConListas() ?? collect();
        if ($component->getName() === 'articulo_id') {
            $set('lista_precio', $precios->keys()->first());
            $set('descuento', 0);
            $set('descuento_porcentaje', 0);
            $set('_descuento_tipo', 'importe');
        }
        $precio = (float) ($precios->get($get('lista_precio'))['precio'] ?? 0);
        $set('precio_unitario', $precio);
        $set('precio_original', $precio);
        $fila = data_get($component->getLivewire(), $component->getContainer()->getStatePath()) ?? [];
        foreach (CalculoDetalle::calcular($fila, 'venta', $component->getContainer()->getStatePath()) as $campo => $valor) {
            $set($campo, $valor);
        }
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
