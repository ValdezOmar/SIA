<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ventas\FacturaResource;
use App\Models\Ventas\Factura;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Widget de la tabla del dashboard que compara, por mes, lo facturado
 * contra el costo de ventas registrado en el kardex, mostrando la
 * ganancia bruta y el porcentaje de rentabilidad.
 */
class GananciaBrutaWidget extends TableWidget
{
    protected static ?string $heading = 'Ventas · Ganancia bruta mensual';

    protected static ?string $description = 'Ingresos, costo de ventas y ganancia bruta por mes.';

    protected static ?int $sort = 15;

    protected static ?string $pollingInterval = '5m';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    /**
     * Query principal del widget.
     *
     * Agrupa las facturas por mes (ven_facturas) y les une por período
     * el costo de ventas acumulado del kardex (alm_kardex) para ese mismo mes.
     *
     * @return Builder
     */
    protected function getTableQuery(): Builder
    {
        $inicio = now()->subMonths(6)->startOfMonth();
        $fin = now()->endOfMonth();
        $empresaId = Auth::user()?->empresa_id;

        // Subconsulta de costos: total del costo de ventas por mes.
        // Solo considera salidas de kardex tipo "venta" confirmadas.
        $costosQuery = DB::table('alm_kardex as k')
            ->selectRaw('DATE_FORMAT(k.fecha_movimiento, "%Y-%m") as periodo, SUM(k.costo_total) as total_costo')
            ->where('k.tipo_movimiento', 'venta')
            ->where('k.direccion', 'salida')
            ->where('k.estado', 'confirmado')
            ->whereBetween('k.fecha_movimiento', [$inicio, $fin])
            ->when($empresaId, fn ($q) => $q->where('k.empresa_id', $empresaId))
            ->groupByRaw('DATE_FORMAT(k.fecha_movimiento, "%Y-%m")');

        return Factura::query()
            ->selectRaw('
                MIN(ven_facturas.id) as id,
                DATE_FORMAT(ven_facturas.fecha_emision, "%Y-%m") as periodo,
                COALESCE(SUM(ven_facturas.total), 0) as total_ventas,
                COALESCE(MAX(costos.total_costo), 0) as total_costo,
                COALESCE(SUM(ven_facturas.total), 0) - COALESCE(MAX(costos.total_costo), 0) as ganancia_bruta
            ')
            ->leftJoinSub($costosQuery, 'costos', function ($join) {
                $join->whereRaw('DATE_FORMAT(ven_facturas.fecha_emision, "%Y-%m") = costos.periodo');
            })
            ->where('ven_facturas.estado', '!=', 'anulada')
            ->whereBetween('ven_facturas.fecha_emision', [$inicio, $fin])
            ->when($empresaId, fn ($q) => $q->where('ven_facturas.empresa_id', $empresaId))
            ->groupByRaw('DATE_FORMAT(ven_facturas.fecha_emision, "%Y-%m")')
            ->orderByDesc('periodo');
    }

    /**
     * Columnas visibles de la tabla.
     *
     * @return array
     */
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('periodo')
                ->label('Período')
                ->formatStateUsing(fn ($state): string => $this->formatPeriodo((string) $state))
                ->searchable(false)
                ->sortable(false),
            TextColumn::make('total_ventas')
                ->label('Ingresos')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd()
                ->searchable(false)
                ->sortable(false),
            TextColumn::make('total_costo')
                ->label('Costo de ventas')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd()
                ->searchable(false)
                ->sortable(false),
            TextColumn::make('ganancia_bruta')
                ->label('Ganancia bruta')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd()
                ->searchable(false)
                ->sortable(false)
                ->color(fn ($record): string => ($record->total_ventas - $record->total_costo) >= 0
                    ? 'success'
                    : 'danger'),
            TextColumn::make('porcentaje')
                ->label('% Ganancia')
                ->formatStateUsing(fn ($record): string => $record->total_ventas > 0
                    ? number_format(($record->ganancia_bruta / $record->total_ventas) * 100, 1, ',', '.') . '%'
                    : '0%')
                ->alignEnd()
                ->searchable(false)
                ->sortable(false),
        ];
    }

    /**
     * Convierte un período "YYYY-MM" en una etiqueta amigable, p. ej. "Abr 2026".
     */
    private function formatPeriodo(string $periodo): string
    {
        $parts = explode('-', $periodo);

        if (count($parts) !== 2) {
            return $periodo;
        }

        $nombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return ($nombres[(int) $parts[1]] ?? $parts[1]) . ' ' . $parts[0];
    }

    public static function canView(): bool
    {
        return FacturaResource::canViewAny();
    }
}