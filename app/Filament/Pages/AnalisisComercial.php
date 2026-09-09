<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AnalisisComercial extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?string $navigationLabel = 'Análisis comercial';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.pages.analisis-comercial';

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
        if (in_array($pestana, ['vendidos', 'rentables', 'clientes', 'resumen'], true)) {
            $this->pestana = $pestana;
            $this->cargar();
        }
    }

    public function updatedPeriodo(): void { $this->cargar(); }

    public function periodos(): array
    {
        return collect(range(0, 11))->mapWithKeys(fn ($i) => [$d = now()->startOfMonth()->subMonths($i)->format('Y-m') => $d])->all();
    }

    private function cargar(): void
    {
        $fecha = Carbon::createFromFormat('Y-m', preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->periodo) ? $this->periodo : now()->format('Y-m'))->startOfMonth();
        $asientos = DB::table('con_asientos_contables')->select('documento_id')->where('documento_tipo', 'venta')->where('estado', 'confirmado')->groupBy('documento_id');
        $facturas = DB::table('ven_facturas as f')->joinSub($asientos, 'a', fn ($j) => $j->on('f.id', '=', 'a.documento_id'))->where('f.estado', '!=', 'anulada')->whereNull('f.deleted_at')->whereBetween('f.fecha_emision', [$fecha, $fecha->copy()->endOfMonth()])->when(Auth::user()?->empresa_id, fn ($q, $id) => $q->where('f.empresa_id', $id));
        $detalles = fn () => (clone $facturas)->join('ven_facturas_detalle as d', 'd.factura_id', '=', 'f.id')->whereNull('d.deleted_at');
        $stocks = DB::table('alm_existencias')->selectRaw('articulo_id, SUM(cantidad_disponible) stock')->groupBy('articulo_id');
        $this->filas = match ($this->pestana) {
            'rentables' => $detalles()->leftJoin('alm_articulos as art', 'art.id', '=', 'd.articulo_id')->leftJoin('alm_fabricantes as fab', 'fab.id', '=', 'art.fabricante_id')->leftJoinSub($stocks, 'stock', fn ($j) => $j->on('stock.articulo_id', '=', 'art.id'))->selectRaw('MAX(d.codigo_articulo) codigo, MAX(art.nombre_comercial) nombre, MAX(art.codigo_alterno) modelo, MAX(art.foto_catalogo) foto, MAX(fab.nombre) marca, MAX(stock.stock) stock, SUM(d.subtotal * COALESCE(f.tasa_cambio,1)) venta')->groupBy('d.articulo_id')->orderByDesc('venta')->limit(5)->get()->map(fn ($r) => ['codigo' => $r->codigo, 'principal' => $r->nombre, 'modelo' => $r->modelo, 'marca' => $r->marca, 'stock' => $r->stock, 'foto' => $r->foto ? Storage::disk('public')->url($r->foto) : null, 'valor' => 'Bs '.number_format($r->venta, 2, ',', '.')])->all(),
            'clientes' => (clone $facturas)->join('ven_clientes as c', 'c.id', '=', 'f.cliente_id')->selectRaw('c.nombre, SUM(f.subtotal * COALESCE(f.tasa_cambio,1)) compra')->groupBy('c.id', 'c.nombre')->orderByDesc('compra')->limit(5)->get()->map(fn ($r) => ['principal' => $r->nombre, 'valor' => 'Bs '.number_format($r->compra, 2, ',', '.')])->all(),
            'resumen' => (function () use ($facturas) { $r = (clone $facturas)->selectRaw('SUM(f.subtotal * COALESCE(f.tasa_cambio,1)) ventas, COUNT(*) facturas')->first(); return [['principal' => 'Ventas netas', 'valor' => 'Bs '.number_format($r->ventas ?? 0, 2, ',', '.')], ['principal' => 'Facturas contabilizadas', 'valor' => (string) ($r->facturas ?? 0)]]; })(),
            default => $detalles()->leftJoin('alm_articulos as art', 'art.id', '=', 'd.articulo_id')->leftJoin('alm_fabricantes as fab', 'fab.id', '=', 'art.fabricante_id')->leftJoinSub($stocks, 'stock', fn ($j) => $j->on('stock.articulo_id', '=', 'art.id'))->selectRaw('MAX(d.codigo_articulo) codigo, MAX(art.nombre_comercial) nombre, MAX(art.codigo_alterno) modelo, MAX(art.foto_catalogo) foto, MAX(fab.nombre) marca, MAX(stock.stock) stock, SUM(d.cantidad) unidades')->groupBy('d.articulo_id')->orderByDesc('unidades')->limit(5)->get()->map(fn ($r) => ['codigo' => $r->codigo, 'principal' => $r->nombre, 'modelo' => $r->modelo, 'marca' => $r->marca, 'stock' => $r->stock, 'foto' => $r->foto ? Storage::disk('public')->url($r->foto) : null, 'valor' => number_format($r->unidades, 2, ',', '.').' unidades'])->all(),
        };
    }
}
