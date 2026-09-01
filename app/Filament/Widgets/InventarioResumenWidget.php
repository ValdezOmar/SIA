<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventario\ArticuloResource;
use App\Filament\Resources\Inventario\StockAlmacenResource;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Lote;
use App\Models\Inventario\TransferenciaAlmacen;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InventarioResumenWidget extends BaseWidget
{
    protected static ?int $sort = 30;

    protected static ?string $pollingInterval = '5m';

    protected function getHeading(): string
    {
        return 'Inventario · Disponibilidad y riesgo';
    }

    protected function getDescription(): string
    {
        return 'Valor almacenado, disponibilidad, compromisos y alertas de reposición.';
    }

    protected function getStats(): array
    {
        $existencias = $this->scopeExistencias(Existencia::query());
        $valor = (float) (clone $existencias)->sum('costo_acumulado');
        $disponible = (float) (clone $existencias)->sum('cantidad_disponible');
        $comprometido = (float) (clone $existencias)->sum('cantidad_comprometida');
        $bajoMinimo = (clone $existencias)
            ->where('cantidad_minima', '>', 0)
            ->whereColumn('cantidad_disponible', '<=', 'cantidad_minima')
            ->count();
        $porVencer = $this->scopeLotes(Lote::query())
            ->whereBetween('fecha_vencimiento', [today(), today()->addDays(30)])
            ->whereHas('stocks', fn (Builder $query): Builder => $query->where('cantidad', '>', 0))
            ->count();
        $enTransito = $this->scopeTransfers(TransferenciaAlmacen::query())
            ->where('estado', 'en_transito')
            ->count();

        return [
            Stat::make('Valor del inventario', $this->money($valor))
                ->description('Costo acumulado en todos los almacenes')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->icon('heroicon-o-circle-stack')
                ->color('primary')
                ->url(StockAlmacenResource::getUrl('index')),
            Stat::make('Unidades disponibles', number_format($disponible, 2, ',', '.'))
                ->description(number_format($comprometido, 2, ',', '.').' unidades comprometidas')
                ->descriptionIcon('heroicon-m-cube')
                ->icon('heroicon-o-cube-transparent')
                ->color('success')
                ->url(StockAlmacenResource::getUrl('index')),
            Stat::make('Stock bajo mínimo', number_format($bajoMinimo))
                ->description($bajoMinimo > 0 ? 'Artículos que requieren reposición' : 'Niveles de stock saludables')
                ->descriptionIcon($bajoMinimo > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-arrow-trending-down')
                ->color($bajoMinimo > 0 ? 'danger' : 'success')
                ->url(ArticuloResource::getUrl('index')),
            Stat::make('Lotes por vencer', number_format($porVencer))
                ->description('Con stock y vencimiento en los próximos 30 días')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-clock')
                ->color($porVencer > 0 ? 'warning' : 'success')
                ->url(ArticuloResource::getUrl('index')),
            Stat::make('Transferencias en tránsito', number_format($enTransito))
                ->description('Pendientes de recepción en almacén destino')
                ->descriptionIcon('heroicon-m-truck')
                ->icon('heroicon-o-arrows-right-left')
                ->color($enTransito > 0 ? 'info' : 'gray')
                ->url(TransferenciaAlmacenResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return ArticuloResource::canViewAny() || StockAlmacenResource::canViewAny();
    }

    private function scopeExistencias(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query
            ->whereHas('articulo', fn (Builder $query): Builder => $query->where('empresa_id', $empresaId)));
    }

    private function scopeLotes(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query
            ->whereHas('articulo', fn (Builder $query): Builder => $query->where('empresa_id', $empresaId)));
    }

    private function scopeTransfers(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query
            ->whereHas('almacenOrigen', fn (Builder $query): Builder => $query->where('empresa_id', $empresaId)));
    }

    private function money(float $amount): string
    {
        return 'Bs '.number_format($amount, 2, ',', '.');
    }
}
