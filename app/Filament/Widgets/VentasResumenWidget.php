<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ventas\CotizacionResource;
use App\Filament\Resources\Ventas\FacturaResource;
use App\Filament\Resources\Ventas\PedidoResource;
use App\Models\Ventas\Cotizacion;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pago;
use App\Models\Ventas\Pedido;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VentasResumenWidget extends BaseWidget
{
    protected static ?int $sort = 20;

    protected static ?string $pollingInterval = '5m';

    protected function getHeading(): string
    {
        return 'Ventas · Rendimiento comercial';
    }

    protected function getDescription(): string
    {
        return 'Facturación, cobranza, cartera y avance del proceso comercial.';
    }

    protected function getStats(): array
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $facturas = $this->scopeCompany(Factura::query());
        $facturadoMes = (float) (clone $facturas)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->where('estado', '!=', 'anulada')
            ->sum('total');
        $cobradoMes = (float) $this->scopeCompany(Pago::query())
            ->where('estado', 'confirmado')
            ->whereBetween('fecha_pago', [$inicioMes, $finMes])
            ->sum('monto');
        $cartera = (float) (clone $facturas)
            ->whereNotIn('estado', ['pagada', 'anulada'])
            ->where('saldo', '>', 0)
            ->sum('saldo');
        $vencido = (float) (clone $facturas)
            ->whereNotIn('estado', ['pagada', 'anulada'])
            ->whereDate('fecha_vencimiento', '<', today())
            ->where('saldo', '>', 0)
            ->sum('saldo');
        $reservados = $this->scopeCompany(Pedido::query())->where('estado', 'reservado')->count();

        $cotizacionesMes = $this->scopeCompany(Cotizacion::query())
            ->whereBetween('fecha_emision', [$inicioMes, $finMes]);
        $totalCotizaciones = (clone $cotizacionesMes)->where('estado', '!=', 'borrador')->count();
        $convertidas = (clone $cotizacionesMes)->whereIn('estado', ['aprobada', 'convertida'])->count();
        $conversion = $totalCotizaciones > 0 ? ($convertidas / $totalCotizaciones) * 100 : 0;

        return [
            Stat::make('Facturación del mes', $this->money($facturadoMes))
                ->description('Documentos no anulados emitidos este mes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(FacturaResource::getUrl('index')),
            Stat::make('Cobrado este mes', $this->money($cobradoMes))
                ->description('Pagos confirmados recibidos')
                ->descriptionIcon('heroicon-m-check-badge')
                ->icon('heroicon-o-wallet')
                ->color('primary')
                ->url(FacturaResource::getUrl('index')),
            Stat::make('Cartera por cobrar', $this->money($cartera))
                ->description($vencido > 0 ? $this->money($vencido).' ya vencidos' : 'Sin saldos vencidos')
                ->descriptionIcon($vencido > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-receipt-percent')
                ->color($vencido > 0 ? 'danger' : 'success')
                ->url(FacturaResource::getUrl('index')),
            Stat::make('Pedidos reservados', number_format($reservados))
                ->description('Esperan preparación o despacho')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-shopping-cart')
                ->color($reservados > 0 ? 'warning' : 'success')
                ->url(PedidoResource::getUrl('index')),
            Stat::make('Conversión de cotizaciones', number_format($conversion, 1, ',', '.').' %')
                ->description("{$convertidas} de {$totalCotizaciones} gestionadas este mes")
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->icon('heroicon-o-presentation-chart-line')
                ->color($conversion >= 50 ? 'success' : ($conversion > 0 ? 'warning' : 'gray'))
                ->url(CotizacionResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return FacturaResource::canViewAny() || PedidoResource::canViewAny() || CotizacionResource::canViewAny();
    }

    private function scopeCompany(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query->where('empresa_id', $empresaId));
    }

    private function money(float $amount): string
    {
        return 'Bs '.number_format($amount, 2, ',', '.');
    }
}
