<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pedido;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StockArticuloFicha extends Component
{
    public int $existenciaId;

    public string $vista = 'resumen';

    public function mount(int $existenciaId): void { $this->existenciaId = $existenciaId; }
    public function refrescar(): void { unset($this->existencia, $this->movimientos, $this->reservasComerciales); }

    public function mostrar(string $vista): void
    {
        if (in_array($vista, ['resumen', 'actividad', 'reservas'], true)) {
            $this->vista = $vista;
        }
    }

    #[Computed]
    public function existencia(): Existencia
    {
        return Existencia::with([
            'almacen:id,nombre,codigo', 'articulo.fabricante:id,nombre', 'articulo.grupoArticulo:id,nombre',
            'articulo.unidadMedida:id,nombre,abreviatura', 'articulo.codigosBarras:id,articulo_id,codigo_barras,tipo,principal',
            'articulo.precios' => fn ($query) => $query->whereHas('listaPrecio', fn ($lista) => $lista->where('activo', true)),
            'articulo.precios.listaPrecio:id,nombre,moneda,activo',
        ])->findOrFail($this->existenciaId);
    }

    #[Computed]
    public function movimientos()
    {
        $stock = $this->existencia;
        return Kardex::where('articulo_id', $stock->articulo_id)->where('almacen_id', $stock->almacen_id)
            ->latest('fecha_movimiento')->limit(6)->get(['id','tipo_movimiento','direccion','cantidad','cantidad_posterior','documento_codigo','fecha_movimiento']);
    }

    #[Computed]
    public function reservasComerciales()
    {
        $stock = $this->existencia;
        $reservas = MovimientoInventario::query()
            ->where('articulo_id', $stock->articulo_id)
            ->where('almacen_id', $stock->almacen_id)
            ->where('estado', 'confirmado')
            ->whereIn('documento_tipo', ['pedido_reserva', 'venta_reserva'])
            ->get(['documento_tipo', 'documento_id', 'documento_codigo', 'cantidad', 'fecha']);

        $resultado = collect();
        $pedidoIds = $reservas->where('documento_tipo', 'pedido_reserva')->pluck('documento_id')->filter()->unique();
        $facturaIds = $reservas->where('documento_tipo', 'venta_reserva')->pluck('documento_id')->filter()->unique();

        Pedido::with(['cliente:id,nombre,razon_social,celular', 'pagos' => fn ($query) => $query->where('estado', 'confirmado')])
            ->whereIn('id', $pedidoIds)
            ->whereIn('estado', ['reservado', 'pendiente', 'parcial'])
            ->get()
            ->each(function (Pedido $pedido) use ($reservas, $resultado): void {
                $reserva = $reservas->where('documento_tipo', 'pedido_reserva')->where('documento_id', $pedido->id);
                $pagos = $pedido->pagos;
                $resultado->push([
                    'origen' => 'Pedido', 'codigo' => $pedido->codigo, 'cliente' => $pedido->cliente?->razon_social ?: $pedido->cliente?->nombre ?: 'Cliente no identificado',
                    'celular' => $pedido->cliente?->celular, 'estado' => $pedido->estado_label, 'color' => $pedido->estado_color,
                    'cantidad' => (float) $reserva->sum('cantidad'), 'fecha_reserva' => $reserva->min('fecha'), 'entrega' => $pedido->fecha_entrega_estimada,
                    'pagado' => (float) $pagos->sum('monto'), 'fecha_pago' => $pagos->min('fecha_pago'), 'total' => (float) $pedido->total,
                ]);
            });

        Factura::with(['cliente:id,nombre,razon_social,celular', 'pagos' => fn ($query) => $query->where('estado', 'confirmado')])
            ->whereIn('id', $facturaIds)
            ->get()
            ->each(function (Factura $factura) use ($reservas, $resultado): void {
                $reserva = $reservas->where('documento_tipo', 'venta_reserva')->where('documento_id', $factura->id);
                $pagos = $factura->pagos;
                $resultado->push([
                    'origen' => 'Factura', 'codigo' => $factura->numero, 'cliente' => $factura->cliente?->razon_social ?: $factura->cliente?->nombre ?: 'Cliente no identificado',
                    'celular' => $factura->cliente?->celular, 'estado' => 'Reserva con pago parcial', 'color' => 'warning',
                    'cantidad' => (float) $reserva->sum('cantidad'), 'fecha_reserva' => $reserva->min('fecha'), 'entrega' => null,
                    'pagado' => (float) $pagos->sum('monto'), 'fecha_pago' => $pagos->min('fecha_pago'), 'total' => (float) $factura->total,
                ]);
            });

        return $resultado->sortBy('fecha_reserva')->values();
    }

    public function render() { return view('livewire.inventario.stock-articulo-ficha'); }
}