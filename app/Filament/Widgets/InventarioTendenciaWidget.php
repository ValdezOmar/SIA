<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventario\KardexResource;
use App\Models\Inventario\Kardex;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class InventarioTendenciaWidget extends ChartWidget
{
    protected static ?string $heading = 'Flujo de inventario · Últimos 6 meses';

    protected static ?string $description = 'Unidades confirmadas que ingresaron y salieron de los almacenes.';

    protected static ?int $sort = 42;

    protected static ?string $pollingInterval = '5m';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected function getData(): array
    {
        $meses = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->startOfMonth()->subMonths($offset));
        $movimientos = $meses->map(function (Carbon $mes): array {
            $query = $this->scopeCompany(Kardex::query())
                ->where('estado', 'confirmado')
                ->whereBetween('fecha_movimiento', [$mes, $mes->copy()->endOfMonth()]);

            return [
                'entradas' => (float) (clone $query)->where('direccion', 'entrada')->sum('cantidad'),
                'salidas' => (float) (clone $query)->where('direccion', 'salida')->sum('cantidad'),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $movimientos->pluck('entradas')->all(),
                    'backgroundColor' => '#22c55e',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Salidas',
                    'data' => $movimientos->pluck('salidas')->all(),
                    'backgroundColor' => '#f97316',
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
        return KardexResource::canViewAny();
    }

    private function scopeCompany(Builder $query): Builder
    {
        $empresaId = Auth::user()?->empresa_id;

        return $query->when($empresaId, fn (Builder $query): Builder => $query->where('empresa_id', $empresaId));
    }
}
