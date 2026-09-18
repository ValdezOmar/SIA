<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasWidgetPermission;
use App\Models\Ventas\Factura;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class VentasTendenciaWidget extends ChartWidget
{
    use HasWidgetPermission;

    protected ?string $heading = 'Ventas cobradas · Últimos 6 meses';

    protected ?string $description = 'Ventas cuyo cobro total fue verificado.';

    protected static ?int $sort = 60;

    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected function getData(): array
    {
        $meses = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->startOfMonth()->subMonths($offset));

        return [
            'datasets' => [
                [
                    'label' => 'Ventas con cobro verificado (Bs)',
                    'data' => $meses->map(fn (Carbon $mes): float => (float) $this->scopeCompany(Factura::query())
                        ->where('estado', 'pagada')
                        ->whereBetween('fecha_emision', [$mes, $mes->copy()->endOfMonth()])
                        ->sum('total'))->all(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.78)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $meses->map(fn (Carbon $mes): string => ucfirst($mes->translatedFormat('M Y')))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'layout' => [
                'padding' => ['top' => 28],
            ],
            'plugins' => [
                'siaValueLabels' => [
                    'enabled' => true,
                    'currency' => 'Bs',
                ],
            ],
        ];
    }

    private function scopeCompany(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query->where('empresa_id', $empresaId));
    }
}
