@php
    use Illuminate\Support\Carbon;
    $record = $getRecord();

    // Verificar permisos - NO usar echo
    if ($user->hasRole('Empleado') && $user->email !== $record->correo_corporativo) {
        // En lugar de echo, generamos contenido vacío
        $showContent = false;
    } else {
        $showContent = true;
    }

    if (!$showContent) {
        // Generar contenido vacío directamente
        return;
    }

    $asistencias = \App\Models\RRHH\Asistencia::where('user_id', $record->ci)
        ->whereDate('fecha', $date)
        ->where('visible', true)
        ->orderBy('hora')
        ->get();

    if ($asistencias->isEmpty()) {
        if ($carbonDate->isWeekend()) {
            echo '<div style="color:rgb(7, 236, 57); font-weight: 500; padding: 5px; font-size: 0.875rem;">F/S</div>';
        } else {
            echo '-';
        }
        return;
    }

    $horaLimite = Carbon::today()->setTime(8, 35, 59);
    $horaOmision = Carbon::today()->setTime(10, 0, 0);
    $primeraMarcacion = Carbon::parse($asistencias->first()->hora);
@endphp

<div style="text-align: center" x-data>
    @if ($primeraMarcacion->greaterThan($horaOmision))
        <span style='color: orange; font-weight: Bold; font-size: 0.875rem;'>Omisión</span><br>
    @endif

    @foreach ($asistencias as $index => $asistencia)
        @php
            $horaCompleta = Carbon::parse($asistencia->hora)->format('H:i:s');
            $esRetraso =
                $index === 0 &&
                Carbon::parse($asistencia->hora)->greaterThan($horaLimite) &&
                Carbon::parse($asistencia->hora)->lessThan($horaOmision);
        @endphp

        @if ($asistencia->registro_remoto)
            <div x-data="{ open: false }" style="display: inline-block">
                <!-- ... resto del código modal ... -->
            </div>
        @elseif($esRetraso)
            <span style='color: red; font-weight: bold; font-size: 0.875rem;'>{{ $horaCompleta }}</span>
        @else
            <span style="font-size: 0.875rem;">{{ $horaCompleta }}</span>
        @endif

        @if (!$loop->last)
            <br>
        @endif
    @endforeach
</div>