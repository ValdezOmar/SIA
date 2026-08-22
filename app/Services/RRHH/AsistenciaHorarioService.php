<?php

namespace App\Services\RRHH;

use App\Models\RRHH\Empleado;
use App\Models\RRHH\HorarioAsistencia;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AsistenciaHorarioService
{
    private static ?bool $tablasDisponibles = null;

    private static ?HorarioAsistencia $horarioPredeterminado = null;

    private static bool $horarioPredeterminadoResuelto = false;

    public static function resolver(Empleado $empleado, Carbon|string $fecha): ?HorarioAsistencia
    {
        if (! (self::$tablasDisponibles ??= Schema::hasTable('rh_horarios_asistencia'))) {
            return null;
        }

        $fecha = Carbon::parse($fecha)->toDateString();

        $asignacion = $empleado->relationLoaded('asignacionesHorarioAsistencia')
            ? $empleado->asignacionesHorarioAsistencia
                ->filter(fn ($asignacion): bool => $asignacion->activo
                    && $asignacion->fecha_inicio?->toDateString() <= $fecha
                    && (! $asignacion->fecha_fin || $asignacion->fecha_fin->toDateString() >= $fecha))
                ->sortByDesc('fecha_inicio')
                ->first()
            : $empleado->asignacionesHorarioAsistencia()
                ->with('horario')
                ->where('activo', true)
                ->whereDate('fecha_inicio', '<=', $fecha)
                ->where(function ($query) use ($fecha): void {
                    $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $fecha);
                })
                ->latest('fecha_inicio')
                ->first();

        if ($asignacion?->horario?->activo) {
            return $asignacion->horario;
        }

        if (! self::$horarioPredeterminadoResuelto) {
            self::$horarioPredeterminado = HorarioAsistencia::query()
                ->where('activo', true)
                ->where('predeterminado', true)
                ->first();
            self::$horarioPredeterminadoResuelto = true;
        }

        return self::$horarioPredeterminado;
    }

    /** @return array{estado: string, retraso_segundos: int, horario: ?HorarioAsistencia} */
    public static function evaluar(Empleado $empleado, Carbon|string $fecha, Collection $marcaciones): array
    {
        $fecha = Carbon::parse($fecha);
        $horario = static::resolver($empleado, $fecha);

        if (! $horario) {
            return ['estado' => 'sin_horario', 'retraso_segundos' => 0, 'horario' => null];
        }

        if (! in_array($fecha->isoWeekday(), array_map('intval', $horario->dias_laborales ?? []), true)) {
            return ['estado' => 'descanso', 'retraso_segundos' => 0, 'horario' => $horario];
        }

        $primera = $marcaciones->sortBy('hora')->first();

        if (! $primera) {
            return ['estado' => 'falta', 'retraso_segundos' => 0, 'horario' => $horario];
        }

        $horaMarcacion = Carbon::parse($fecha->toDateString().' '.$primera->hora);
        $horaEntrada = Carbon::parse($fecha->toDateString().' '.$horario->hora_entrada);
        $horaLimite = $horaEntrada->copy()->addMinutes($horario->tolerancia_minutos);

        if ($horario->hora_omision && $horaMarcacion->greaterThan(Carbon::parse($fecha->toDateString().' '.$horario->hora_omision))) {
            return ['estado' => 'omision', 'retraso_segundos' => 0, 'horario' => $horario];
        }

        if ($horaMarcacion->greaterThan($horaLimite)) {
            return [
                'estado' => 'retraso',
                'retraso_segundos' => $horaEntrada->diffInSeconds($horaMarcacion),
                'horario' => $horario,
            ];
        }

        return ['estado' => 'puntual', 'retraso_segundos' => 0, 'horario' => $horario];
    }
}
