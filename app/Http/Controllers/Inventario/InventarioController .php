<?php

namespace App\Http\Controllers\Inventario;

use App\Models\Inventario\Articulo;
use App\Models\Inventario\Almacen;
use App\Services\Inventario\CapaCostoService;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    protected $capaCostoService;

    public function __construct(CapaCostoService $capaCostoService)
    {
        $this->capaCostoService = $capaCostoService;
    }

    /**
     * Registrar una compra (entrada de inventario)
     */
    public function registrarCompra(Request $request)
    {
        $articulo = Articulo::find($request->articulo_id);
        $almacen = Almacen::find($request->almacen_id);

        // Crear capa de costo
        $capa = $this->capaCostoService->crearCapa(
            $articulo,
            $almacen,
            $request->cantidad,
            $request->costo_unitario,
            $request->movimiento_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Capa de costo creada exitosamente',
            'data' => $capa
        ]);
    }

    /**
     * Registrar una venta (salida de inventario)
     */
    public function registrarVenta(Request $request)
    {
        $articulo = Articulo::find($request->articulo_id);
        $almacen = Almacen::find($request->almacen_id);

        // Procesar salida FIFO
        $resultado = $this->capaCostoService->procesarSalida(
            $articulo,
            $almacen,
            $request->cantidad,
            $request->venta_id
        );

        return response()->json([
            'success' => true,
            'message' => 'Venta procesada exitosamente',
            'data' => $resultado
        ]);
    }

    /**
     * Obtener el costo promedio de un artículo en un almacén
     */
    public function getCostoPromedio(Request $request)
    {
        $articulo = Articulo::find($request->articulo_id);
        $almacen = Almacen::find($request->almacen_id);

        $costoPromedio = $this->capaCostoService->calcularCostoPromedio($articulo, $almacen);
        $costoTotal = $this->capaCostoService->getCostoInventario($articulo, $almacen);

        return response()->json([
            'success' => true,
            'data' => [
                'costo_promedio' => $costoPromedio,
                'costo_total_inventario' => $costoTotal,
                'detalle_capas' => $this->capaCostoService->getDetalleCapas($articulo, $almacen),
            ]
        ]);
    }
}