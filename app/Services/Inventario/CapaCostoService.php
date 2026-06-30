<?php

namespace App\Services\Inventario;

use App\Models\Inventario\Articulo;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\CapaCosto;
use App\Models\Inventario\MovimientoInventario;
use Illuminate\Support\Facades\DB;

class CapaCostoService
{
    /**
     * Crear una nueva capa de costo (entrada de inventario)
     */
    public function crearCapa(Articulo $articulo, Almacen $almacen, $cantidad, $costoUnitario, $movimientoId = null, $fecha = null)
    {
        return CapaCosto::create([
            'articulo_id' => $articulo->id,
            'almacen_id' => $almacen->id,
            'movimiento_id' => $movimientoId,
            'cantidad_original' => $cantidad,
            'cantidad_disponible' => $cantidad,
            'costo_unitario' => $costoUnitario,
            'fecha' => $fecha ?? now(),
        ]);
    }

    /**
     * Procesar una salida de inventario usando FIFO
     */
    public function procesarSalida(Articulo $articulo, Almacen $almacen, $cantidad, $movimientoId = null)
    {
        $capas = CapaCosto::where('articulo_id', $articulo->id)
            ->where('almacen_id', $almacen->id)
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $cantidadSalida = $cantidad;
        $costoTotal = 0;
        $capasUtilizadas = [];

        foreach ($capas as $capa) {
            if ($cantidadSalida <= 0) {
                break;
            }

            $cantidadUsar = min($cantidadSalida, $capa->cantidad_disponible);
            
            // Registrar el costo
            $costoTotal += $cantidadUsar * $capa->costo_unitario;
            
            // Actualizar la capa
            $capa->cantidad_disponible -= $cantidadUsar;
            $capa->save();

            $capasUtilizadas[] = [
                'capa_id' => $capa->id,
                'cantidad' => $cantidadUsar,
                'costo_unitario' => $capa->costo_unitario,
                'subtotal' => $cantidadUsar * $capa->costo_unitario,
            ];

            $cantidadSalida -= $cantidadUsar;
        }

        // Crear el movimiento de salida
        $movimiento = MovimientoInventario::create([
            'articulo_id' => $articulo->id,
            'almacen_id' => $almacen->id,
            'tipo' => 'salida_venta',
            'cantidad' => -$cantidad,
            'costo_unitario' => $cantidad > 0 ? $costoTotal / $cantidad : 0,
            'costo_total' => $costoTotal,
            'documento_tipo' => 'venta',
            'documento_id' => $movimientoId ?? 0,
            'fecha' => now(),
            'observacion' => 'Salida FIFO',
        ]);

        // Registrar las capas utilizadas en el movimiento
        foreach ($capasUtilizadas as $capaUsada) {
            $movimiento->capasCostos()->create([
                'articulo_id' => $articulo->id,
                'almacen_id' => $almacen->id,
                'cantidad_original' => $capaUsada['cantidad'],
                'cantidad_disponible' => 0,
                'costo_unitario' => $capaUsada['costo_unitario'],
                'fecha' => now(),
            ]);
        }

        return [
            'movimiento' => $movimiento,
            'capas_utilizadas' => $capasUtilizadas,
            'costo_total' => $costoTotal,
        ];
    }

    /**
     * Calcular el costo promedio del inventario
     */
    public function calcularCostoPromedio(Articulo $articulo, Almacen $almacen)
    {
        $capas = CapaCosto::where('articulo_id', $articulo->id)
            ->where('almacen_id', $almacen->id)
            ->where('cantidad_disponible', '>', 0)
            ->get();

        $totalCantidad = $capas->sum('cantidad_disponible');
        
        if ($totalCantidad == 0) {
            return 0;
        }

        $totalCosto = $capas->sum(function ($capa) {
            return $capa->cantidad_disponible * $capa->costo_unitario;
        });

        return $totalCosto / $totalCantidad;
    }

    /**
     * Obtener el detalle de capas de un artículo
     */
    public function getDetalleCapas(Articulo $articulo, Almacen $almacen)
    {
        return CapaCosto::where('articulo_id', $articulo->id)
            ->where('almacen_id', $almacen->id)
            ->where('cantidad_disponible', '>', 0)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->map(function ($capa) {
                return [
                    'id' => $capa->id,
                    'fecha' => $capa->fecha->format('d/m/Y H:i'),
                    'cantidad_disponible' => $capa->cantidad_disponible,
                    'costo_unitario' => $capa->costo_unitario,
                    'costo_total' => $capa->cantidad_disponible * $capa->costo_unitario,
                ];
            });
    }

    /**
     * Obtener el costo del inventario disponible
     */
    public function getCostoInventario(Articulo $articulo, Almacen $almacen)
    {
        $capas = CapaCosto::where('articulo_id', $articulo->id)
            ->where('almacen_id', $almacen->id)
            ->where('cantidad_disponible', '>', 0)
            ->get();

        return $capas->sum(function ($capa) {
            return $capa->cantidad_disponible * $capa->costo_unitario;
        });
    }
}