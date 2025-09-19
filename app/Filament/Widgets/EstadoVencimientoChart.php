<?php

namespace App\Filament\Widgets;

use App\Models\Almacen\Articulo;
use Filament\Widgets\ChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class EstadoVencimientoChart extends ChartWidget
{
    protected static ?string $heading = 'Stock por vencer';
    use HasWidgetShield;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $hoy = now();
        $almacenesPermitidos = [101, 102, 107, 202, 207, 302, 307, 402, 407, 502, 507];

        $empresas = Articulo::query()
            ->whereNotNull('empresa')
            ->whereIn('cod_almacen', $almacenesPermitidos)
            ->where('saldo_actual', '>', 0)
            ->distinct()
            ->orderBy('empresa')
            ->pluck('empresa');

        $coloresEmpresa = [
            '#6366f1',
            '#10b981',
            '#f59e0b',
            '#ef4444',
            '#3b82f6',
            '#8b5cf6',
            '#ec4899',
            '#14b8a6',
            '#0ea5e9',
            '#f97316',
            '#84cc16',
            '#eab308',
            '#22c55e',
            '#a855f7',
        ];

        $labels = ['Vencido', '≤ 4 meses', '4–8 meses'];
        $dataPorEmpresa = [];

        foreach ($empresas as $index => $empresa) {
            $query = Articulo::query()
                ->whereIn('cod_almacen', $almacenesPermitidos)
                ->whereNotNull('fecha_ven')
                ->where('saldo_actual', '>', 0)
                ->where('empresa', $empresa);

            $vencido = (clone $query)->whereDate('fecha_ven', '<', $hoy)->count();
            $menos4 = (clone $query)->whereBetween('fecha_ven', [$hoy, $hoy->copy()->addMonths(4)])->count();
            $entre4y8 = (clone $query)->whereBetween('fecha_ven', [$hoy->copy()->addMonths(4), $hoy->copy()->addMonths(8)])->count();

            $dataPorEmpresa[] = [
                'label' => $empresa,
                'data' => [$vencido, $menos4, $entre4y8],
                'backgroundColor' => $coloresEmpresa[$index % count($coloresEmpresa)],
                'barThickness' => 25,
                'borderRadius' => 6,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $dataPorEmpresa,
        ];
    }
}