<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ventas\FacturaResource;
use App\Filament\Widgets\Concerns\HasWidgetPermission;
use Filament\Widgets\Widget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalisisComercialWidget extends Widget
{
    use HasWidgetPermission;

    protected static string $view = 'filament.widgets.analisis-comercial-widget';

    protected static ?int $sort = 16;

    protected static ?string $pollingInterval = '5m';

    protected int|string|array $columnSpan = 'full';

    public string $pestana = 'productos_vendidos';

    public string $periodo;

    public function mount(): void
    {
        $this->periodo = now()->format('Y-m');
    }

    public function seleccionarPestana(string $pestana): void
    {
        if (array_key_exists($pestana, $this->pestanas())) {
            $this->pestana = $pestana;
        }
    }

    /** @return array<string, string> */
    public function pestanas(): array
    {
        return [
            'productos_vendidos' => 'Productos más vendidos',
            'productos_rentables' => 'Productos más rentables',
            'clientes' => 'Clientes con mayor compra',
            'resumen' => 'Resumen del mes',
        ];
    }

    /** @return array<string, string> */
    public function periodos(): array
    {
        return collect(range(0, 11))
            ->map(fn (int $mes): Carbon => now()->startOfMonth()->subMonths($mes))
            ->mapWithKeys(fn (Carbon $fecha): array => [
                $fecha->format('Y-m') => ucfirst($fecha->translatedFormat('F Y')),
            ])
            ->all();
    }

    /** @return array<int, array<string, float|int|string>> */
    public function filas(): array
    {
        return match ($this->pestana) {
            'productos_rentables' => $this->productosRentables(),
            'clientes' => $this->clientesConMayorCompra(),
            'resumen' => $this->resumenMensual(),
            default => $this->productosMasVendidos(),
        };
    }

    /** @return array<int, string> */
    public function columnas(): array
    {
        return match ($this->pestana) {
            'productos_rentables' => ['Producto', 'Unidades', 'Venta neta', 'Costo', 'Ganancia', 'Margen'],
            'clientes' => ['Cliente', 'Facturas', 'Compra neta', 'Última compra'],
            'resumen' => ['Indicador', 'Valor', 'Detalle'],
            default => ['Producto', 'Unidades', 'Venta neta', 'Descuentos'],
        };
    }

    /** @return array<int, array<string, float|int|string>> */
    private function productosMasVendidos(): array
    {
        return $this->detallesBase()
            ->selectRaw('MIN(detalles.id) as id, MAX(detalles.codigo_articulo) as codigo, MAX(detalles.descripcion_articulo) as producto, SUM(detalles.cantidad) as unidades, SUM(detalles.subtotal * COALESCE(facturas.tasa_cambio, 1)) as venta_neta, SUM(detalles.descuento * COALESCE(facturas.tasa_cambio, 1)) as descuentos')
            ->groupBy('detalles.articulo_id')
            ->orderByDesc('unidades')
            ->orderByDesc('venta_neta')
            ->limit(5)
            ->get()
            ->map(fn ($fila): array => [
                'producto' => $this->producto($fila),
                'unidades' => (float) $fila->unidades,
                'venta_neta' => (float) $fila->venta_neta,
                'descuentos' => (float) $fila->descuentos,
            ])->all();
    }

    /** @return array<int, array<string, float|int|string>> */
    private function productosRentables(): array
    {
        $costos = DB::table('alm_kardex')
            ->selectRaw('documento_id, documento_detalle_id, SUM(costo_total) as costo')
            ->where('documento_tipo', 'venta')
            ->where('tipo_movimiento', 'venta')
            ->where('direccion', 'salida')
            ->where('estado', 'confirmado')
            ->groupBy('documento_id', 'documento_detalle_id');

        return $this->detallesBase()
            ->leftJoinSub($costos, 'costos', fn ($join) => $join
                ->on('costos.documento_id', '=', 'facturas.id')
                ->on('costos.documento_detalle_id', '=', 'detalles.id'))
            ->selectRaw('MIN(detalles.id) as id, MAX(detalles.codigo_articulo) as codigo, MAX(detalles.descripcion_articulo) as producto, SUM(detalles.cantidad) as unidades, SUM(detalles.subtotal * COALESCE(facturas.tasa_cambio, 1)) as venta_neta, SUM(COALESCE(costos.costo, 0)) as costo')
            ->groupBy('detalles.articulo_id')
            ->orderByRaw('SUM(detalles.subtotal * COALESCE(facturas.tasa_cambio, 1)) - SUM(COALESCE(costos.costo, 0)) DESC')
            ->limit(5)
            ->get()
            ->map(function ($fila): array {
                $venta = (float) $fila->venta_neta;
                $costo = (float) $fila->costo;
                $ganancia = $venta - $costo;

                return [
                    'producto' => $this->producto($fila),
                    'unidades' => (float) $fila->unidades,
                    'venta_neta' => $venta,
                    'costo' => $costo,
                    'ganancia' => $ganancia,
                    'margen' => $venta > 0 ? ($ganancia / $venta) * 100 : 0,
                ];
            })->all();
    }

    /** @return array<int, array<string, float|int|string>> */
    private function clientesConMayorCompra(): array
    {
        return $this->facturasBase()
            ->join('ven_clientes as clientes', 'clientes.id', '=', 'facturas.cliente_id')
            ->selectRaw('MIN(facturas.id) as id, clientes.nombre as cliente, COUNT(facturas.id) as facturas, SUM(facturas.subtotal * COALESCE(facturas.tasa_cambio, 1)) as compra_neta, MAX(facturas.fecha_emision) as ultima_compra')
            ->groupBy('clientes.id', 'clientes.nombre')
            ->orderByDesc('compra_neta')
            ->limit(5)
            ->get()
            ->map(fn ($fila): array => [
                'cliente' => (string) $fila->cliente,
                'facturas' => (int) $fila->facturas,
                'compra_neta' => (float) $fila->compra_neta,
                'ultima_compra' => Carbon::parse($fila->ultima_compra)->format('d/m/Y'),
            ])->all();
    }

    /** @return array<int, array<string, float|int|string>> */
    private function resumenMensual(): array
    {
        $facturas = $this->facturasBase()
            ->selectRaw('COUNT(*) as facturas, COUNT(DISTINCT facturas.cliente_id) as clientes, SUM(facturas.subtotal * COALESCE(facturas.tasa_cambio, 1)) as ventas, SUM(facturas.descuento * COALESCE(facturas.tasa_cambio, 1)) as descuentos')
            ->first();
        $unidades = (float) $this->detallesBase()->sum('detalles.cantidad');
        $ventas = (float) ($facturas->ventas ?? 0);
        $cantidadFacturas = (int) ($facturas->facturas ?? 0);

        return [
            ['indicador' => 'Ventas netas', 'valor' => $ventas, 'detalle' => 'Importe neto de impuestos indirectos, en bolivianos.'],
            ['indicador' => 'Descuentos concedidos', 'valor' => (float) ($facturas->descuentos ?? 0), 'detalle' => 'Descuentos aplicados en las líneas de venta.'],
            ['indicador' => 'Ticket promedio', 'valor' => $cantidadFacturas > 0 ? $ventas / $cantidadFacturas : 0, 'detalle' => $cantidadFacturas.' factura(s) contabilizada(s).'],
            ['indicador' => 'Clientes activos', 'valor' => (int) ($facturas->clientes ?? 0), 'detalle' => 'Clientes que compraron durante el período.'],
            ['indicador' => 'Unidades vendidas', 'valor' => $unidades, 'detalle' => 'Unidades de productos y servicios facturadas.'],
        ];
    }

    private function facturasBase(): Builder
    {
        [$inicio, $fin] = $this->fechasPeriodo();
        $ventasContabilizadas = DB::table('con_asientos_contables')
            ->select('documento_id')
            ->where('documento_tipo', 'venta')
            ->where('estado', 'confirmado')
            ->groupBy('documento_id');

        return DB::table('ven_facturas as facturas')
            ->joinSub($ventasContabilizadas, 'ventas_contabilizadas', fn ($join) => $join->on('facturas.id', '=', 'ventas_contabilizadas.documento_id'))
            ->where('facturas.estado', '!=', 'anulada')
            ->whereNull('facturas.deleted_at')
            ->whereBetween('facturas.fecha_emision', [$inicio, $fin])
            ->when(Auth::user()?->empresa_id, fn (Builder $query, $empresaId) => $query->where('facturas.empresa_id', $empresaId));
    }

    private function detallesBase(): Builder
    {
        return $this->facturasBase()
            ->join('ven_facturas_detalle as detalles', 'detalles.factura_id', '=', 'facturas.id')
            ->whereNull('detalles.deleted_at');
    }

    /** @return array{Carbon, Carbon} */
    private function fechasPeriodo(): array
    {
        $periodo = array_key_exists($this->periodo, $this->periodos()) ? $this->periodo : now()->format('Y-m');
        $fecha = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

        return [$fecha, $fecha->copy()->endOfMonth()];
    }

    private function producto(object $fila): string
    {
        $codigo = trim((string) ($fila->codigo ?? ''));
        $nombre = trim((string) ($fila->producto ?? 'Sin descripción'));

        return $codigo !== '' ? $codigo.' · '.$nombre : $nombre;
    }

    public static function canView(): bool
    {
        return static::canViewWithShieldPermission() && FacturaResource::canViewAny();
    }
}
