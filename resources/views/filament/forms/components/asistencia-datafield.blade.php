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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Columna Izquierda: Información + Justificación -->
                                <div class="space-y-6">
                                    <!-- Información del registro -->
                                    <div class="space-y-4">
                                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Fecha</h3>
                                        <p class="text-lg font-semibold text-gray-800 dark:text-white">
                                            {{ Carbon::parse($asistencia->fecha)->translatedFormat('l, d F Y') }}
                                        </p>

                                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Hora</h3>
                                        <p class="text-lg text-gray-800 dark:text-white">{{ $horaCompleta }}</p>

                                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Equipo</h3>
                                        <p class="text-sm text-gray-800 dark:text-white leading-relaxed">
                                            @if($asistencia->id_equipo)
                                                @php
                                                    try {
                                                        $equipoData = json_decode($asistencia->id_equipo, true);
                                                        $userAgent = $equipoData['userAgent'] ?? '';
                                                        $platform = $equipoData['platform'] ?? '';

                                                        $os = 'Sistema desconocido';
                                                        $deviceModel = '';

                                                        if (preg_match('/Android\s([0-9\.]+)/i', $userAgent, $matches)) {
                                                            $os = 'Android ' . ($matches[1] ?? '');
                                                            if (preg_match('/; ([a-zA-Z0-9]+) Build/i', $userAgent, $modelMatches)) {
                                                                $deviceModel = str_replace('_', ' ', $modelMatches[1]);
                                                            }
                                                        } elseif (preg_match('/iPhone|iPod|iPad/i', $userAgent)) {
                                                            $os = 'iOS';
                                                            if (preg_match('/iPhone(\d+,\d+)/i', $userAgent, $modelMatches) ||
                                                                preg_match('/iPhone (\d+)/i', $userAgent, $modelMatches)) {
                                                                $deviceModel = 'iPhone ' . str_replace(',', '.', $modelMatches[1]);
                                                            }
                                                        } elseif (preg_match('/Windows NT/i', $userAgent)) {
                                                            $os = 'Windows';
                                                            if (preg_match('/Windows NT (\d+\.\d+)/i', $userAgent, $versionMatches)) {
                                                                $versions = ['10.0' => '10/11','6.3' => '8.1','6.2' => '8','6.1' => '7'];
                                                                $os .= ' ' . ($versions[$versionMatches[1]] ?? $versionMatches[1]);
                                                            }
                                                        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
                                                            $os = 'Mac';
                                                        } elseif (preg_match('/Linux/i', $userAgent)) {
                                                            $os = 'Linux';
                                                        }

                                                        $browser = 'Navegador desconocido';
                                                        if (preg_match('/Chrome\/([\d\.]+)/i', $userAgent)) {
                                                            $browser = 'Chrome';
                                                        } elseif (preg_match('/Firefox\/([\d\.]+)/i', $userAgent)) {
                                                            $browser = 'Firefox';
                                                        } elseif (preg_match('/Safari\/([\d\.]+)/i', $userAgent)) {
                                                            $browser = 'Safari';
                                                        } elseif (preg_match('/Edge\/([\d\.]+)/i', $userAgent)) {
                                                            $browser = 'Edge';
                                                        }

                                                        $displayText = "$os";
                                                        if ($deviceModel) {
                                                            $displayText .= " ($deviceModel)";
                                                        }
                                                        $displayText .= " - $browser";

                                                        if (isset($equipoData['deviceHash'])) {
                                                            $displayText .= " (ID: " . substr($equipoData['deviceHash'], 0, 6) . ")";
                                                        }

                                                        echo $displayText;
                                                    } catch (Exception $e) {
                                                        echo 'Dispositivo registrado';
                                                    }
                                                @endphp
                                            @else
                                                No registrado
                                            @endif
                                        </p>
                                    </div>

                                    <!-- Justificación -->
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Justificación</h3>
                                        <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                            <p class="text-sm text-gray-800 dark:text-gray-100">
                                                {{ $asistencia->justificacion ?? 'No especificada' }}
                                            </p>
                                        </div>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Ubicación registrada</h3>
                                    <p class="text-sm text-gray-800 dark:text-gray-200">
                                        {{ $asistencia->localizacion ?? 'No registrada' }}
                                    </p>
                                </div>

                                <!-- Columna Derecha: Mapa -->
                                <div class="space-y-4">    
                                    @if ($asistencia->localizacion)
                                        <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm h-96">
                                            <iframe 
                                                width="100%" height="100%" frameborder="0" scrolling="no"
                                                src="https://maps.google.com/maps?q={{ urlencode($asistencia->localizacion) }}&z=16&output=embed"
                                                class="rounded-xl"
                                            ></iframe>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 italic">Mapa proporcionado por Google</p>
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
