<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ventas\FacturaResource;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pago;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class VentasTendenciaWidget extends ChartWidget
{
    protected static ?string $heading = 'Ventas y cobranza · Últimos 6 meses';

    protected static ?string $description = 'Compara lo facturado con el efectivo realmente cobrado.';

    protected static ?int $sort = 40;

    protected static ?string $pollingInterval = '5m';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected function getData(): array
    {
        $meses = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->startOfMonth()->subMonths($offset));

        return [
            'datasets' => [
                [
                    'label' => 'Facturado (Bs)',
                    'data' => $meses->map(fn (Carbon $mes): float => (float) $this->scopeCompany(Factura::query())
                        ->where('estado', '!=', 'anulada')
                        ->whereBetween('fecha_emision', [$mes, $mes->copy()->endOfMonth()])
                        ->sum('total'))->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Cobrado (Bs)',
                    'data' => $meses->map(fn (Carbon $mes): float => (float) $this->scopeCompany(Pago::query())
                        ->where('estado', 'confirmado')
                        ->whereBetween('fecha_pago', [$mes, $mes->copy()->endOfMonth()])
                        ->sum('monto'))->all(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $meses->map(fn (Carbon $mes): string => ucfirst($mes->translatedFormat('M Y')))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return FacturaResource::canViewAny();
    }

    private function scopeCompany(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query->where('empresa_id', $empresaId));
    }
}
