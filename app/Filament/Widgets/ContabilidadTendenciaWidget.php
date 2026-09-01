<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Contabilidad\AsientoContableResource;
use App\Models\Contabilidad\AsientoContable;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ContabilidadTendenciaWidget extends ChartWidget
{
    protected static ?string $heading = 'Movimiento contable · Últimos 6 meses';

    protected static ?string $description = 'Control mensual de cargos y abonos confirmados.';

    protected static ?int $sort = 41;

    protected static ?string $pollingInterval = '5m';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected function getData(): array
    {
        $meses = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->startOfMonth()->subMonths($offset));
        $movimientos = $meses->map(function (Carbon $mes): array {
            $query = $this->scopeCompany(AsientoContable::query())
                ->where('estado', 'confirmado')
                ->whereBetween('fecha_asiento', [$mes, $mes->copy()->endOfMonth()]);

            return [
                'debe' => (float) (clone $query)->sum('total_debe'),
                'haber' => (float) (clone $query)->sum('total_haber'),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Debe (Bs)',
                    'data' => $movimientos->pluck('debe')->all(),
                    'backgroundColor' => '#0ea5e9',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Haber (Bs)',
                    'data' => $movimientos->pluck('haber')->all(),
                    'backgroundColor' => '#8b5cf6',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $meses->map(fn (Carbon $mes): string => ucfirst($mes->translatedFormat('M Y')))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return AsientoContableResource::canViewAny();
    }

    private function scopeCompany(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query->where('empresa_id', $empresaId));
    }
}
