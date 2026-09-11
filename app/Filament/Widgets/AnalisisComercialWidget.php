<?php

namespace App\Filament\Widgets;

use App\Exports\AnalisisComercialVentasExport;
use App\Filament\Resources\Ventas\FacturaResource;
use App\Filament\Widgets\Concerns\HasWidgetPermission;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AnalisisComercialWidget extends Widget
{
    use HasWidgetPermission;

    protected static ?int $sort = 25;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.analisis-comercial';

    public string $periodo = '';

    public string $pestana = 'vendidos';

    public array $filas = [];

    public function mount(): void
    {
        $this->periodo = now()->format('Y-m');
        $this->cargar();
    }

    public function seleccionar(string $pestana): void
    {
        if (! in_array($pestana, ['vendidos', 'rentables', 'clientes', 'resumen'], true)) {
            return;
        }

        $this->pestana = $pestana;
        $this->cargar();
    }

    public function updatedPeriodo(): void
    {
        $this->cargar();
    }

    public function exportarVentas()
    {
        $periodo = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->periodo)
            ? $this->periodo
            : now()->format('Y-m');

        return Excel::download(
            new AnalisisComercialVentasExport($periodo, Auth::user()?->empresa_id),
            "analisis-comercial-ventas-{$periodo}.xlsx",
        );
    }

    public function periodos(): array
    {
        return collect(range(0, 11))
            ->mapWithKeys(fn (int $mesesAtras): array => [
                $periodo = now()->startOfMonth()->subMonths($mesesAtras)->format('Y-m') => $periodo,
            ])
            ->all();
    }

    public static function canView(): bool
    {
        return static::canViewWithShieldPermission() && FacturaResource::canViewAny();
    }

    public function cargar(): void
    {
        $periodo = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->periodo)
            ? $this->periodo
            : now()->format('Y-m');
        $fecha = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
        $asientos = DB::table('con_asientos_contables')
            ->select('documento_id')
            ->where('documento_tipo', 'venta')
            ->where('estado', 'confirmado')
            ->groupBy('documento_id');
        $facturas = DB::table('ven_facturas as f')
            ->joinSub($asientos, 'a', fn ($join) => $join->on('f.id', '=', 'a.documento_id'))
            ->where('f.estado', '!=', 'anulada')
            ->whereNull('f.deleted_at')
            ->whereBetween('f.fecha_emision', [$fecha, $fecha->copy()->endOfMonth()])
            ->when(Auth::user()?->empresa_id, fn ($query, $empresaId) => $query->where('f.empresa_id', $empresaId));
        $detalles = fn () => (clone $facturas)
            ->join('ven_facturas_detalle as d', 'd.factura_id', '=', 'f.id')
            ->whereNull('d.deleted_at');
        $stocks = DB::table('alm_existencias')
            ->selectRaw('articulo_id, SUM(cantidad_disponible) stock')
            ->groupBy('articulo_id');

        $this->filas = match ($this->pestana) {
            'rentables' => $this->filasDeArticulo($detalles(), $stocks, 'SUM(d.subtotal * COALESCE(f.tasa_cambio, 1)) venta', 'venta', 'Bs '),
            'clientes' => (clone $facturas)
                ->join('ven_clientes as c', 'c.id', '=', 'f.cliente_id')
                ->selectRaw('c.nombre, SUM(f.subtotal * COALESCE(f.tasa_cambio, 1)) compra')
                ->groupBy('c.id', 'c.nombre')
                ->orderByDesc('compra')
                ->limit(5)
                ->get()
                ->map(fn ($fila): array => ['principal' => $fila->nombre, 'valor' => 'Bs '.number_format($fila->compra, 2, ',', '.')])
                ->all(),
            'resumen' => $this->resumen((clone $facturas)->selectRaw('SUM(f.subtotal * COALESCE(f.tasa_cambio, 1)) ventas, COUNT(*) facturas')->first()),
            default => $this->filasDeArticulo($detalles(), $stocks, 'SUM(d.cantidad) unidades', 'unidades', ''),
        };
    }

    private function filasDeArticulo($detalles, $stocks, string $agregado, string $campo, string $prefijo): array
    {
        return $detalles
            ->leftJoin('alm_articulos as art', 'art.id', '=', 'd.articulo_id')
            ->leftJoin('alm_fabricantes as fab', 'fab.id', '=', 'art.fabricante_id')
            ->leftJoinSub($stocks, 'stock', fn ($join) => $join->on('stock.articulo_id', '=', 'art.id'))
            ->selectRaw("MAX(d.codigo_articulo) codigo, MAX(art.nombre_comercial) nombre, MAX(art.codigo_alterno) modelo, MAX(art.foto_catalogo) foto, MAX(fab.nombre) marca, MAX(stock.stock) stock, {$agregado}")
            ->groupBy('d.articulo_id')
            ->orderByDesc($campo)
            ->limit(5)
            ->get()
            ->map(fn ($fila): array => [
                'codigo' => $fila->codigo,
                'principal' => $fila->nombre,
                'modelo' => $fila->modelo,
                'marca' => $fila->marca,
                'stock' => $fila->stock,
                'foto' => $fila->foto ? Storage::disk('public')->url($fila->foto) : null,
                'valor' => $campo === 'unidades'
                    ? number_format($fila->{$campo}, 2, ',', '.').' unidades'
                    : $prefijo.number_format($fila->{$campo}, 2, ',', '.'),
            ])
            ->all();
    }

    private function resumen(object|null $resultado): array
    {
        return [
            ['principal' => 'Ventas netas', 'valor' => 'Bs '.number_format($resultado->ventas ?? 0, 2, ',', '.')],
            ['principal' => 'Facturas contabilizadas', 'valor' => (string) ($resultado->facturas ?? 0)],
        ];
    }
}
