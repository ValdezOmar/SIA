<?php

namespace App\Livewire\Ventas;

use App\Models\Ventas\Cliente;
use Livewire\Component;

class ClienteResumenComercial extends Component
{
    public ?Cliente $record = null;

    public function render()
    {
        if (! $this->record) {
            return view('livewire.ventas.cliente-resumen-comercial', ['resumen' => null]);
        }

        $facturas = $this->record->facturas()->where('estado', '!=', 'anulada');
        $emitidas = (clone $facturas)->whereNotIn('estado', ['borrador']);
        $facturado = (float) ((clone $emitidas)->selectRaw('COALESCE(SUM(total * COALESCE(tasa_cambio, 1)), 0) AS total')->value('total') ?? 0);
        $saldo = max(0, (float) ((clone $emitidas)->selectRaw('COALESCE(SUM(saldo * COALESCE(tasa_cambio, 1)), 0) AS total')->value('total') ?? 0));
        $cobrado = (float) ($this->record->pagos()->where('estado', 'confirmado')->selectRaw('COALESCE(SUM(monto * COALESCE(tasa_cambio, 1)), 0) AS total')->value('total') ?? 0);
        $ultimaFactura = (clone $emitidas)->latest('fecha_emision')->first(['numero', 'fecha_emision']);
        $pedidos = $this->record->pedidos()->whereNotIn('estado', ['cancelado', 'anulado', 'entregado', 'facturado'])->count();

        return view('livewire.ventas.cliente-resumen-comercial', [
            'resumen' => compact('facturado', 'saldo', 'cobrado', 'pedidos', 'ultimaFactura') + [
                'facturas' => $emitidas->count(),
            ],
        ]);
    }
}