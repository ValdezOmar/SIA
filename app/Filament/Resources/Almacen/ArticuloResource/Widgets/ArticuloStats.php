<?php

namespace App\Filament\Resources\Almacen\ArticuloResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Resources\Almacen\ArticuloResource\Pages\ListArticulos;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\HtmlString;

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

        // Obtener sincronizaciones
        $syncs = DB::table('alm_sync_control')
            ->whereIn('nombre_proceso', [
                'novanexa_to_sia',
                'requilab_to_sia',
                'sap b1 ireilab_to_sia',
            ])
            ->get()
            ->mapWithKeys(function ($row) {
                $alias = match ($row->nombre_proceso) {
                    'novanexa_to_sia' => 'Nov',
                    'requilab_to_sia' => 'Req',
                    'sap b1 ireilab_to_sia' => 'Ire',
                    default => $row->nombre_proceso,
                };
                return [
                    $alias => Carbon::parse($row->ultima_sincronizacion)->format('d/m H:i'),
                ];
            });

        // Crear el HTML para descripción
        $syncHtmlParts = [];
        foreach ($syncs as $nombre => $fecha) {
            $syncHtmlParts[] = "<strong>{$nombre}:</strong> {$fecha}";
        }
        $syncHtml = implode(' &nbsp;|&nbsp; ', $syncHtmlParts);
        $syncHtmlWrapped = new HtmlString("<div style='font-size:0.75rem; color:#6b720; margin-top:4px;'>{$syncHtml}</div>");

        return [
            
            Stat::make('Total de Artículos y fecha de actualizacion', Number::format($totalItems))
                ->description($syncHtmlWrapped)
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('success'),

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