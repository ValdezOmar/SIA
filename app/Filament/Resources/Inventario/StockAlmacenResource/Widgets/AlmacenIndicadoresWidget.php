<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Widgets;

use App\Models\Inventario\Almacen;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class AlmacenIndicadoresWidget extends BaseWidget
{
    public ?Almacen $record = null;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        if (! $this->record || ! Schema::hasTable('alm_existencias')) {
            return [];
        }

        $existencias = $this->record->existencias();
        $articulos = (clone $existencias)->distinct('articulo_id')->count('articulo_id');
        $fisico = (float) (clone $existencias)->sum('cantidad_disponible');
        $reservado = (float) (clone $existencias)->sum('cantidad_comprometida');
        $libre = max(0, $fisico - $reservado);
        $bajoMinimo = (clone $existencias)
            ->where('cantidad_minima', '>', 0)
            ->whereRaw('(cantidad_disponible - cantidad_comprometida) <= cantidad_minima')
            ->count();
        $movimientosHoy = Schema::hasTable('alm_kardex')
            ? $this->record->kardex()->whereDate('fecha_movimiento', today())->count()
            : 0;

        return [
            Stat::make('Artículos con stock', number_format($articulos))
                ->description('Referencias activas en '.$this->record->nombre)
                ->descriptionIcon('heroicon-m-cube')
                ->icon('heroicon-o-squares-2x2')
                ->color('primary'),
            Stat::make('Unidades libres', number_format($libre, 2))
                ->description('Disponibles para venta o consumo')
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make('Unidades reservadas', number_format($reservado, 2))
                ->description('Comprometidas en pedidos activos')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->icon('heroicon-o-shopping-cart')
                ->color('warning'),
            Stat::make('Bajo mínimo', number_format($bajoMinimo))
                ->description($bajoMinimo > 0 ? 'Artículos que requieren reposición' : 'Niveles de stock saludables')
                ->descriptionIcon($bajoMinimo > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($bajoMinimo > 0 ? 'danger' : 'success'),
            Stat::make('Movimientos hoy', number_format($movimientosHoy))
                ->description('Entradas, salidas y ajustes registrados hoy')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info'),
        ];
    }
}