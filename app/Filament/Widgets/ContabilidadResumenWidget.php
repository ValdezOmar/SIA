<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Contabilidad\AsientoContableResource;
use App\Filament\Resources\Contabilidad\PlanCuentaResource;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Contabilidad\PeriodoContable;
use App\Models\Contabilidad\PlanCuenta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ContabilidadResumenWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected static ?string $pollingInterval = '5m';

    protected function getHeading(): string
    {
        return 'Contabilidad · Control financiero';
    }

    protected function getDescription(): string
    {
        return 'Situación del mes actual y alertas que requieren revisión contable.';
    }

    protected function getStats(): array
    {
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();
        $asientosMes = $this->scopeCompany(AsientoContable::query())
            ->whereBetween('fecha_asiento', [$inicioMes, $finMes]);

        $movimientoMes = (float) (clone $asientosMes)
            ->where('estado', 'confirmado')
            ->sum('total_debe');
        $borradores = (clone $asientosMes)->where('estado', 'borrador')->count();
        $descuadrados = (clone $asientosMes)
            ->where('estado', '!=', 'anulado')
            ->whereRaw('ABS(total_debe - total_haber) > 0.01')
            ->count();
        $periodosAbiertos = $this->scopeCompany(PeriodoContable::query())->where('estado', 'abierto')->count();
        $cuentasActivas = $this->scopeCompany(PlanCuenta::query())->where('activo', true)->count();

        return [
            Stat::make('Movimiento confirmado del mes', $this->money($movimientoMes))
                ->description('Suma del Debe en asientos confirmados')
                ->descriptionIcon('heroicon-m-check-badge')
                ->icon('heroicon-o-scale')
                ->color('success')
                ->url(AsientoContableResource::getUrl('index')),
            Stat::make('Asientos por confirmar', number_format($borradores))
                ->description($borradores > 0 ? 'Pendientes de revisión y autorización' : 'Contabilidad al día')
                ->descriptionIcon($borradores > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-document-text')
                ->color($borradores > 0 ? 'warning' : 'success')
                ->url(AsientoContableResource::getUrl('index')),
            Stat::make('Asientos descuadrados', number_format($descuadrados))
                ->description($descuadrados > 0 ? 'Requieren corrección inmediata' : 'Todos los asientos están balanceados')
                ->descriptionIcon($descuadrados > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-exclamation-circle')
                ->color($descuadrados > 0 ? 'danger' : 'success')
                ->url(AsientoContableResource::getUrl('index')),
            Stat::make('Períodos abiertos', number_format($periodosAbiertos))
                ->description('Períodos aún habilitados para movimientos')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar')
                ->color($periodosAbiertos > 1 ? 'warning' : 'primary'),
            Stat::make('Cuentas activas', number_format($cuentasActivas))
                ->description('Cuentas disponibles en el plan contable')
                ->descriptionIcon('heroicon-m-list-bullet')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(PlanCuentaResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return AsientoContableResource::canViewAny() || PlanCuentaResource::canViewAny();
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
