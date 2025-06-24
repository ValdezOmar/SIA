<?php

namespace App\Filament\Resources\Almacen\ArticuloResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Resources\Almacen\ArticuloResource\Pages\ListArticulos;
use Illuminate\Support\Number;

class ArticuloStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListArticulos::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();
        $hoy = now();

        // Conteos de vencimiento
        $vencido = $query->clone()
            ->whereDate('fecha_ven', '<', $hoy)
            ->count();
            
        $menos4 = $query->clone()
            ->whereBetween('fecha_ven', [$hoy, $hoy->copy()->addMonths(4)])
            ->count();
            
        $entre4y8 = $query->clone()
            ->whereBetween('fecha_ven', [$hoy->copy()->addMonths(4), $hoy->copy()->addMonths(8)])
            ->count();
            
        $mas8 = $query->clone()
            ->whereDate('fecha_ven', '>', $hoy->copy()->addMonths(8))
            ->count();
            
        $sinFecha = $query->clone()
            ->whereNull('fecha_ven')
            ->count();

        // Cálculo de valores totales
        $totalItems = $query->count();
        $totalStock = $query->sum('saldo_actual');
        
        return [
            Stat::make('Total de Artículos', Number::format($totalItems))
                ->description('Items en el listado actual')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('primary'),
                
            Stat::make('Stock Total', Number::format($totalStock))
                ->description('Unidades disponibles en el listado')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('success'),
                
            Stat::make('Estado de Vencimiento', '')
                ->view('filament.widgets.articulo-vencimiento-stats', [
                    'vencido' => $vencido,
                    'menos4' => $menos4,
                    'entre4y8' => $entre4y8,
                    'mas8' => $mas8,
                    'sinFecha' => $sinFecha,
                    'total' => $totalItems,
                ]),
        ];
    }
}