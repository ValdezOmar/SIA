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
                <button type="button"
                    style="color: blue; font-weight: 500; background: none; border: none; padding: 0; cursor: pointer; font-size: 0.875rem;"
                    x-on:click="open = true" @click.stop>
                    {{ $horaCompleta }}(R)
                </button>

                <div x-show="open" x-transition style="display: none; position: fixed; inset: 0; z-index: 50;">
                    <!-- Fondo oscuro -->
                    <div x-on:click="open = false" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);">
                    </div>

                    <!-- Contenedor del modal -->
                    <div
                        style="position: relative; height: 100%; display: flex; align-items: center; justify-content: center; padding: 1rem;">
                        <div class="overflow-hidden bg-white rounded-lg shadow-xl dark:bg-gray-800"
                            style="width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">

                            <!-- Encabezado -->
                            <div class="px-6 py-4 bg-primary-500">
                                <h3 class="text-lg font-semibold text-white">Detalles de Marcación Remota</h3>
                                <p class="text-sm text-primary-100">{{ $record->nombres }} {{ $record->apellidos }} -
                                    CI: {{ $record->ci }}</p>
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 p-6 space-y-4 overflow-y-auto">
                                <!-- Información del registro -->
                                <div class="grid grid-cols-4 gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha</p>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ Carbon::parse($asistencia->fecha)->translatedFormat('l, d F Y') }}
                                        </p>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Hora</p>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $horaCompleta }}</p>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Equipo</p>
                                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                            {{ $asistencia->id_equipo ?? 'No registrado' }}</p>
                                    </div>

                                </div>

                                <!-- Justificación -->
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Justificación</p>
                                    <p class="p-3 mt-1 text-sm text-gray-900 rounded bg-gray-50 dark:bg-gray-700 dark:text-white">
                                        {{ $asistencia->justificacion ?? 'No especificada' }}
                                    </p>
                                </div>

                                <!-- Mapa -->
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ubicación registrada</p>
                                    <p class="mt-1 mb-2 text-sm text-gray-900 dark:text-white">
                                        {{ $asistencia->localizacion ?? 'No registrada' }}</p>

                                    @if ($asistencia->localizacion)
                                        <div class="overflow-hidden bg-gray-100 border border-gray-200 rounded-lg h-96 dark:bg-gray-700 dark:border-gray-600">
                                            <iframe width="100%" height="100%" frameborder="0" scrolling="no"
                                                src="https://maps.google.com/maps?q={{ urlencode($asistencia->localizacion) }}&z=16&output=embed"
                                                style="border:0;">
                                            </iframe>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Mapa proporcionado por Google</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Pie de página con estilo oscuro mejorado -->
                            <div class="flex justify-end px-6 py-3 bg-gray-50 dark:bg-gray-800">
                                <button x-on:click="open = false"
                                    class="px-4 py-2 text-sm text-white transition-colors rounded-md bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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

@if ($carbonDate->isWeekend())
    <style>
        .weekend-cell {
            color: rgb(60, 218, 20);
            padding: 5px;
            font-size: 0.875rem;
        }
    </style>
@endif
