<?php

namespace App\Exports;

use App\Models\Inventario\Existencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockPorSucursalExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles
{
    public function __construct(
        private readonly ?int $empresaId,
        private readonly bool $accesoGlobal = false,
    ) {}

    public function headings(): array
    {
        return [
            'EMPRESA',
            'SUCURSAL',
            'ALMACÉN',
            'CÓDIGO',
            'CÓDIGO ALTERNO',
            'PRODUCTO',
            'UNIDAD',
            'CANTIDAD DISPONIBLE',
            'CANTIDAD COMPROMETIDA',
            'STOCK LIBRE',
            'COSTO PROMEDIO UNITARIO',
            'ÚLTIMO COSTO UNITARIO',
            'VALOR TOTAL DEL STOCK',
            'PRECIOS DE VENTA ACTIVOS',
        ];
    }

    public function query(): Builder
    {
        return Existencia::query()
            ->where('cantidad_disponible', '>=', 1)
            ->whereHas('articulo', fn (Builder $query): Builder => $query
                ->where('inventariable', true)
                ->when($this->empresaId, fn (Builder $query, int $empresaId): Builder => $query->where('empresa_id', $empresaId)))
            ->whereHas('almacen', fn (Builder $query): Builder => $query
                ->when($this->empresaId, fn (Builder $query, int $empresaId): Builder => $query->where('empresa_id', $empresaId))
                ->when(! $this->empresaId && ! $this->accesoGlobal, fn (Builder $query): Builder => $query->whereRaw('1 = 0')))
            ->with([
                'almacen.empresa',
                'almacen.sucursal',
                'articulo.unidadMedida',
                'articulo.precios' => fn (Relation $query): Relation => $query
                    ->whereHas('listaPrecio', fn (Builder $lista): Builder => $lista->where('activo', true)),
                'articulo.precios.listaPrecio',
            ])
            ->orderBy('almacen_id')
            ->orderBy('articulo_id');
    }

    public function map($existencia): array
    {
        $articulo = $existencia->articulo;
        $almacen = $existencia->almacen;
        $cantidadDisponible = (float) $existencia->cantidad_disponible;
        $cantidadComprometida = (float) $existencia->cantidad_comprometida;
        $precios = $articulo?->precios
            ->map(fn ($precio): string => sprintf(
                '%s: %s %s',
                $precio->listaPrecio?->nombre ?? 'Lista sin nombre',
                number_format((float) $precio->precio, 2, '.', ''),
                $precio->listaPrecio?->moneda ?? '',
            ))
            ->implode(' | ') ?? 'Sin precio activo';

        return [
            $almacen?->empresa?->nombre_comercial ?: $almacen?->empresa?->razon_social,
            $almacen?->sucursal?->nombre ?? 'Sin sucursal asignada',
            $almacen?->nombre,
            $articulo?->codigo,
            $articulo?->codigo_alterno,
            $articulo?->nombre_comercial ?: $articulo?->descripcion,
            $articulo?->unidadMedida?->abreviatura,
            $cantidadDisponible,
            $cantidadComprometida,
            $cantidadDisponible - $cantidadComprometida,
            (float) $existencia->costo_promedio,
            (float) $existencia->ultimo_costo,
            (float) $existencia->costo_acumulado,
            $precios,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
