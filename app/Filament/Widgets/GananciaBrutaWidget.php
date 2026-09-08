<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ventas\FacturaResource;
use App\Filament\Widgets\Concerns\HasWidgetPermission;
use App\Models\Ventas\Factura;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GananciaBrutaWidget extends TableWidget
{
    use HasWidgetPermission;

    protected static ?string $heading = 'Ganancia mensual';

    protected static ?string $description = 'Ventas contabilizadas, expresadas en bolivianos y sin impuestos indirectos.';

    protected static ?int $sort = 15;

    protected static ?string $pollingInterval = '5m';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected function getTableQuery(): Builder
    {
        $inicio = now()->subMonths(6)->startOfMonth();
        $fin = now()->endOfMonth();
        $empresaId = Auth::user()?->empresa_id;
        $periodoFactura = $this->periodoSql('ven_facturas.fecha_emision');

        // El asiento confirmado identifica las ventas ya reconocidas; evita
        // incluir borradores, anulaciones o facturas sin efecto contable.
        $ventasContabilizadas = DB::table('con_asientos_contables')
            ->select('documento_id')
            ->where('documento_tipo', 'venta')
            ->where('estado', 'confirmado')
            ->groupBy('documento_id');

        // Se agrupa por factura, no por fecha física de entrega. Así el costo
        // queda en el mismo período comercial de la venta que lo originó.
        $costosPorFactura = DB::table('alm_kardex')
            ->selectRaw('documento_id, SUM(costo_total) as total_costo')
            ->where('documento_tipo', 'venta')
            ->where('tipo_movimiento', 'venta')
            ->where('direccion', 'salida')
            ->where('estado', 'confirmado')
            ->groupBy('documento_id');

        return Factura::query()
            ->selectRaw("\n                MIN(ven_facturas.id) as id,\n                {$periodoFactura} as periodo,\n                SUM(COALESCE(ven_facturas.subtotal, 0) * COALESCE(ven_facturas.tasa_cambio, 1)) as ingresos_netos,\n                SUM(COALESCE(ven_facturas.descuento, 0) * COALESCE(ven_facturas.tasa_cambio, 1)) as descuentos,\n                SUM(COALESCE(costos.total_costo, 0)) as costo_ventas,\n                SUM(COALESCE(ven_facturas.subtotal, 0) * COALESCE(ven_facturas.tasa_cambio, 1))\n                    - SUM(COALESCE(costos.total_costo, 0)) as ganancia_despues_costo\n            ")
            ->joinSub($ventasContabilizadas, 'ventas_contabilizadas', fn ($join) => $join->on('ven_facturas.id', '=', 'ventas_contabilizadas.documento_id'))
            ->leftJoinSub($costosPorFactura, 'costos', fn ($join) => $join->on('ven_facturas.id', '=', 'costos.documento_id'))
            ->where('ven_facturas.estado', '!=', 'anulada')
            ->whereBetween('ven_facturas.fecha_emision', [$inicio, $fin])
            ->when($empresaId, fn ($query) => $query->where('ven_facturas.empresa_id', $empresaId))
            ->groupByRaw($periodoFactura)
            ->orderByDesc('periodo');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('periodo')
                ->label('Período')
                ->formatStateUsing(fn ($state): string => $this->formatPeriodo((string) $state)),
            TextColumn::make('ingresos_netos')
                ->label('Ventas netas')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd(),
            TextColumn::make('descuentos')
                ->label('Descuentos')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd()
                ->color('warning'),
            TextColumn::make('costo_ventas')
                ->label('Costo de ventas')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd()
                ->color('danger'),
            TextColumn::make('ganancia_despues_costo')
                ->label('Ganancia después del costo')
                ->money('BOB', divideBy: 1, locale: 'es')
                ->alignEnd()
                ->color(fn ($record): string => (float) $record->ganancia_despues_costo >= 0 ? 'success' : 'danger'),
            TextColumn::make('rendimiento')
                ->label('Rendimiento')
                ->tooltip('Ganancia después del costo dividida entre ventas netas.')
                ->formatStateUsing(fn ($record): string => (float) $record->ingresos_netos > 0
                    ? number_format(((float) $record->ganancia_despues_costo / (float) $record->ingresos_netos) * 100, 2, ',', '.').' %'
                    : '0,00 %')
                ->alignEnd()
                ->color(fn ($record): string => (float) $record->ganancia_despues_costo >= 0 ? 'success' : 'danger'),
        ];
    }

    private function periodoSql(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function formatPeriodo(string $periodo): string
    {
        $partes = explode('-', $periodo);
        $meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return count($partes) === 2
            ? ($meses[(int) $partes[1]] ?? $partes[1]).' '.$partes[0]
            : $periodo;
    }

    public static function canView(): bool
    {
        return static::canViewWithShieldPermission() && FacturaResource::canViewAny();
    }
}
