<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalisisComercialVentasExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithStyles
{
    public function __construct(
        private readonly string $periodo,
        private readonly ?int $empresaId,
    ) {}

    public function headings(): array
    {
        return [
            'FECHA', 'CÓDIGO', 'NOMBRE', 'TOTAL UNIDADES', 'PRECIO UNITARIO',
            'DESCUENTO', 'TOTAL A PAGAR', 'COSTO', 'UTILIDAD', 'MÉTODO DE PAGO',
            'NO. RECIBO', 'CLIENTE', 'NÚMERO CELULAR',
        ];
    }

    public function collection(): Collection
    {
        $fecha = Carbon::createFromFormat('Y-m', $this->periodo)->startOfMonth();
        $asientos = DB::table('con_asientos_contables')
            ->select('documento_id')
            ->where('documento_tipo', 'venta')
            ->where('estado', 'confirmado')
            ->groupBy('documento_id');
        $costos = DB::table('alm_kardex')
            ->selectRaw('documento_id, documento_detalle_id, MAX(almacen_id) as almacen_id, SUM(costo_total) as costo_total')
            ->where('documento_tipo', 'venta')
            ->where('tipo_movimiento', 'venta')
            ->where('estado', 'confirmado')
            ->whereNull('deleted_at')
            ->groupBy('documento_id', 'documento_detalle_id');
        $pagos = DB::table('ven_pagos')
            ->select('factura_id', 'tipo_pago')
            ->where('estado', 'confirmado')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->groupBy('factura_id')
            ->map(fn (Collection $items): string => $items->pluck('tipo_pago')->unique()->map(
                fn (string $metodo): string => str($metodo)->replace('_', ' ')->title()->toString(),
            )->implode(', '));

        return DB::table('ven_facturas as f')
            ->joinSub($asientos, 'a', fn ($join) => $join->on('f.id', '=', 'a.documento_id'))
            ->join('ven_facturas_detalle as d', 'd.factura_id', '=', 'f.id')
            ->join('ven_clientes as c', 'c.id', '=', 'f.cliente_id')
            ->leftJoin('alm_articulos as art', 'art.id', '=', 'd.articulo_id')
            ->leftJoinSub($costos, 'k', fn ($join) => $join
                ->on('k.documento_id', '=', 'f.id')
                ->on('k.documento_detalle_id', '=', 'd.id'))
            ->where('f.estado', '!=', 'anulada')
            ->whereNull('f.deleted_at')
            ->whereNull('d.deleted_at')
            ->whereBetween('f.fecha_emision', [$fecha, $fecha->copy()->endOfMonth()])
            ->when($this->empresaId, fn ($query, $empresaId) => $query->where('f.empresa_id', $empresaId))
            ->orderBy('f.fecha_emision')
            ->orderBy('f.id')
            ->orderBy('d.id')
            ->get([
                'f.id as factura_id', 'f.fecha_emision', 'f.serie as serie_factura',
                'art.codigo as codigo_articulo', DB::raw('COALESCE(art.nombre_comercial, d.descripcion_articulo) as nombre_comercial'), 'd.precio_unitario',
                'd.cantidad', 'd.total', 'd.subtotal', 'd.descuento', 'f.tasa_cambio', 'c.nombre as cliente', 'c.celular',
                DB::raw('COALESCE(k.costo_total, 0) as costo'),
            ])
            ->map(function (object $fila) use ($pagos): array {
                $total = (float) $fila->total;
                $costo = (float) $fila->costo;
                $ventaNeta = (float) $fila->subtotal * (float) $fila->tasa_cambio;

                return [
                    Carbon::parse($fila->fecha_emision)->format('d/m/Y'),
                    $fila->codigo_articulo,
                    $fila->nombre_comercial,
                    (float) $fila->cantidad,
                    (float) $fila->precio_unitario,
                    (float) $fila->descuento,
                    $total,
                    $costo,
                    max(0.0, $ventaNeta - $costo),
                    $pagos->get($fila->factura_id, 'Pendiente'),
                    $fila->serie_factura,
                    $fila->cliente,
                    $fila->celular,
                ];
            });
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
