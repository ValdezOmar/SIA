<?php

namespace App\Filament\Exports;

use App\Models\RRHH\Empleado;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AsistenciaExport extends Exporter
{
    protected static ?string $model = Empleado::class;

    public static function getColumns(): array
    {
        $mesSeleccionado = request()->input('filters.mes', now()->format('Y-m'));
        $periodo = \App\Filament\Resources\RRHH\AsistenciaResource::getPeriodoFechas($mesSeleccionado);
        $fechaInicio = $periodo['inicio'];
        $fechaFin = $periodo['fin'];

        $uniqueDates = DB::table('asistencias')
            ->select(DB::raw('DATE(fecha) as date'))
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        $columns = [
            ExportColumn::make('ci')
                ->label('CI'),
            ExportColumn::make('nombres')
                ->label('Nombres'),
            ExportColumn::make('apellidos')
                ->label('Apellidos'),
            ExportColumn::make('sucursal')
                ->label('Sucursal'),
            ExportColumn::make('empresa')
                ->label('Empresa'),
        ];

        foreach ($uniqueDates as $date) {
            $carbonDate = Carbon::parse($date);
            $formattedDate = $carbonDate->format('d/m/Y');
            
            $columns[] = ExportColumn::make("asistencias_{$date}")
                ->label("Fecha {$formattedDate}")
                ->state(function (Empleado $record) use ($date, $carbonDate) {
                    $asistencias = \App\Models\RRHH\Asistencia::where('user_id', $record->ci)
                        ->whereDate('fecha', $date)
                        ->orderBy('hora')
                        ->get();

                    if ($asistencias->isEmpty()) {
                        return $carbonDate->isWeekend() ? 'Fin de Semana' : 'Falta';
                    }

                    $result = [];
                    $horaLimite = Carbon::today()->setTime(8, 35, 0);
                    $horaOmision = Carbon::today()->setTime(10, 0, 0);
                    $primeraMarcacion = Carbon::parse($asistencias->first()->hora);

                    // Primera columna: Fecha
                    $fila = "Fecha: {$carbonDate->format('d/m/Y')}\n";

                    // Segunda columna: Primera marcación u omisión
                    if ($primeraMarcacion->greaterThan($horaOmision)) {
                        $fila .= "Omisión";
                    } else {
                        $fila .= $primeraMarcacion->format('H:i:s');
                    }

                    // Tercera columna: Minutos de retraso (si aplica)
                    $fila .= "\n";
                    if ($primeraMarcacion->greaterThan($horaLimite) && !$primeraMarcacion->greaterThan($horaOmision)) {
                        $fila .= $primeraMarcacion->diffInMinutes($horaLimite) . " min";
                    } else {
                        $fila .= "0";
                    }

                    return $fila;
                });
        }

        return $columns;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Tu exportación de asistencias se ha completado y se han exportado ' . number_format($export->successful_rows) . ' filas.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' filas no se pudieron exportar.';
        }

        return $body;
    }
}