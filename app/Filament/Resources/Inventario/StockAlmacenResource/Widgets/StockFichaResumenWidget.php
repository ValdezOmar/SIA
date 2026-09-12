<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Widgets;

use App\Models\Inventario\Existencia;
use App\Models\Inventario\MovimientoInventario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockFichaResumenWidget extends BaseWidget
{
    public ?Existencia $record = null;

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $stock = $this->record;
        $fisico = (float) $stock->cantidad_disponible;
        $reservado = (float) $stock->cantidad_comprometida;
        $libre = max(0, $fisico - $reservado);
        $minimo = (float) $stock->cantidad_minima;
        $reservasActivas = MovimientoInventario::query()
            ->where('articulo_id', $stock->articulo_id)
            ->where('almacen_id', $stock->almacen_id)
            ->where('estado', 'confirmado')
            ->whereIn('documento_tipo', ['pedido_reserva', 'venta_reserva'])
            ->count();

        return [
            Stat::make('Stock físico', number_format($fisico, 2))
                ->description('Unidades registradas en '.$stock->almacen?->nombre)
                ->descriptionIcon('heroicon-m-cube')
                ->icon('heroicon-o-cube')
                ->color('primary'),
            Stat::make('Libre para vender', number_format($libre, 2))
                ->description($libre > 0 ? 'Puede comprometerse ahora' : 'No hay unidades disponibles')
                ->descriptionIcon($libre > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->icon('heroicon-o-check-badge')
                ->color($libre > 0 ? 'success' : 'danger'),
            Stat::make('Reservado', number_format($reservado, 2))
                ->description($reservasActivas.' reserva(s) comercial(es) activas')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->icon('heroicon-o-shopping-cart')
                ->color($reservado > 0 ? 'warning' : 'gray'),
            Stat::make('Valor disponible', 'Bs '.number_format((float) $stock->costo_acumulado, 2))
                ->description('Costo promedio: Bs '.number_format((float) $stock->costo_promedio, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Reposición', $minimo > 0 ? number_format($minimo, 2).' mínimo' : 'Sin mínimo')
                ->description($minimo > 0 && $libre <= $minimo ? 'Requiere reposición' : 'Nivel saludable')
                ->descriptionIcon($minimo > 0 && $libre <= $minimo ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-arrow-trending-up')
                ->color($minimo > 0 && $libre <= $minimo ? 'danger' : 'success'),
        ];
    }
}