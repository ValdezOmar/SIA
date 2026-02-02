@php
    use Illuminate\Support\Carbon;
    $record = $getRecord();

    // Verificar permisos
    if ($user->hasRole('Empleado') && $user->email !== $record->correo_corporativo) {
        echo '';
        return;
    }

    $asistencias = \App\Models\RRHH\Asistencia::where('user_id', $record->ci)
        ->whereDate('fecha', $date)
        ->where('visible', true)
        ->orderBy('hora')
        ->get();

    if ($asistencias->isEmpty()) {
        echo $carbonDate->isWeekend() ? '<div style="color:rgb(7, 236, 57); font-weight: 500; padding: 5px; font-size: 0.875rem;">F/S</div>' : '-';
        return;
    }

    $horaLimite = Carbon::today()->setTime(8, 35, 59);
    $horaOmision = Carbon::today()->setTime(10, 0, 0);
    $primeraMarcacion = Carbon::parse($asistencias->first()->hora);
@endphp

