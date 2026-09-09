<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnalisisComercialController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tipo = $request->string('tipo')->value();
        $tipo = in_array($tipo, ['vendidos', 'rentables', 'clientes', 'resumen'], true) ? $tipo : 'vendidos';
        $periodo = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('periodo')) ? $request->query('periodo') : now()->format('Y-m');
        $fecha = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
        $empresaId = $request->user()?->empresa_id;
        $asientos = DB::table('con_asientos_contables')->select('documento_id')->where('documento_tipo', 'venta')->where('estado', 'confirmado')->groupBy('documento_id');
        $facturas = DB::table('ven_facturas as f')->joinSub($asientos, 'a', fn ($join) => $join->on('f.id', '=', 'a.documento_id'))->where('f.estado', '!=', 'anulada')->whereNull('f.deleted_at')->whereBetween('f.fecha_emision', [$fecha, $fecha->copy()->endOfMonth()])->when($empresaId, fn ($query) => $query->where('f.empresa_id', $empresaId));
        $detalles = fn () => (clone $facturas)->join('ven_facturas_detalle as d', 'd.factura_id', '=', 'f.id')->whereNull('d.deleted_at');

        if ($tipo === 'vendidos') {
            $filas = $detalles()->leftJoin('alm_articulos as art', 'art.id', '=', 'd.articulo_id')->selectRaw('MAX(d.codigo_articulo) codigo, MAX(d.descripcion_articulo) producto, MAX(art.foto_catalogo) foto, SUM(d.cantidad) unidades, SUM(d.subtotal * COALESCE(f.tasa_cambio, 1)) ventas')->groupBy('d.articulo_id')->orderByDesc('unidades')->orderByDesc('ventas')->limit(5)->get();
            return response()->json(['columnas' => ['Producto', 'Unidades', 'Venta neta'], 'filas' => $filas->map(fn ($r) => ['producto' => [(string) $r->codigo, (string) $r->producto, $r->foto ? Storage::disk('public')->url($r->foto) : null], 'celdas' => [number_format($r->unidades, 2, ',', '.'), 'Bs '.number_format($r->ventas, 2, ',', '.')]])]);
        }
        if ($tipo === 'rentables') {
            $costos = DB::table('alm_kardex')->selectRaw('documento_id, documento_detalle_id, SUM(costo_total) costo')->where('documento_tipo', 'venta')->where('tipo_movimiento', 'venta')->where('direccion', 'salida')->where('estado', 'confirmado')->groupBy('documento_id', 'documento_detalle_id');
            $filas = $detalles()->leftJoin('alm_articulos as art', 'art.id', '=', 'd.articulo_id')->leftJoinSub($costos, 'k', fn ($join) => $join->on('k.documento_id', '=', 'f.id')->on('k.documento_detalle_id', '=', 'd.id'))->selectRaw('MAX(d.codigo_articulo) codigo, MAX(d.descripcion_articulo) producto, MAX(art.foto_catalogo) foto, SUM(d.subtotal * COALESCE(f.tasa_cambio, 1)) ventas, SUM(COALESCE(k.costo,0)) costo')->groupBy('d.articulo_id')->orderByRaw('SUM(d.subtotal * COALESCE(f.tasa_cambio, 1)) - SUM(COALESCE(k.costo,0)) DESC')->limit(5)->get();
            return response()->json(['columnas' => ['Producto', 'Venta neta', 'Costo', 'Ganancia'], 'filas' => $filas->map(fn ($r) => ['producto' => [(string) $r->codigo, (string) $r->producto, $r->foto ? Storage::disk('public')->url($r->foto) : null], 'celdas' => ['Bs '.number_format($r->ventas, 2, ',', '.'), 'Bs '.number_format($r->costo, 2, ',', '.'), 'Bs '.number_format($r->ventas - $r->costo, 2, ',', '.')]])]);
        }
        if ($tipo === 'clientes') {
            $filas = (clone $facturas)->join('ven_clientes as c', 'c.id', '=', 'f.cliente_id')->selectRaw('c.nombre, COUNT(f.id) facturas, SUM(f.subtotal * COALESCE(f.tasa_cambio,1)) compras')->groupBy('c.id', 'c.nombre')->orderByDesc('compras')->limit(5)->get();
            return response()->json(['columnas' => ['Cliente', 'Facturas', 'Compra neta'], 'filas' => $filas->map(fn ($r) => [(string) $r->nombre, (string) $r->facturas, 'Bs '.number_format($r->compras, 2, ',', '.')])]);
        }
        $totales = (clone $facturas)->selectRaw('COUNT(*) facturas, COUNT(DISTINCT f.cliente_id) clientes, SUM(f.subtotal * COALESCE(f.tasa_cambio,1)) ventas, SUM(f.descuento * COALESCE(f.tasa_cambio,1)) descuentos')->first();
        $ventas = (float) ($totales->ventas ?? 0);
        return response()->json(['columnas' => ['Indicador', 'Valor'], 'filas' => [['Ventas netas', 'Bs '.number_format($ventas, 2, ',', '.')], ['Descuentos', 'Bs '.number_format((float) ($totales->descuentos ?? 0), 2, ',', '.')], ['Ticket promedio', 'Bs '.number_format($totales->facturas ? $ventas / $totales->facturas : 0, 2, ',', '.')], ['Clientes activos', (string) ($totales->clientes ?? 0)]]]);
    }
}
