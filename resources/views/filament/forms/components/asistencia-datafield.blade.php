@php
    use Illuminate\Support\Carbon;

    $record = $getRecord();

    if ($user->hasRole('Empleado') && $user->email !== $record->historialActivo?->correo_corporativo) {
        return;
    }

    $asistencias = \App\Models\RRHH\Asistencia::query()
        ->where('user_id', $record->ci)
        ->whereDate('fecha', $date)
        ->where('visible', true)
        ->orderBy('hora')
        ->get();
    $evaluacion = \App\Services\RRHH\AsistenciaHorarioService::evaluar($record, $carbonDate, $asistencias);
@endphp

@if ($evaluacion['estado'] === 'descanso')
    <span class="sia-attendance-cell sia-attendance-cell--rest">Descanso</span>
@elseif ($evaluacion['estado'] === 'sin_horario')
    <span class="sia-attendance-cell sia-attendance-cell--schedule">Sin horario</span>
@elseif ($evaluacion['estado'] === 'falta')
    <span class="sia-attendance-cell sia-attendance-cell--absence">Falta</span>
@else
    <div class="sia-attendance-cell" x-data>
        @if ($evaluacion['estado'] === 'omision')
            <span class="sia-attendance-cell--omission">Omisión</span>
        @endif

        @foreach ($asistencias as $index => $asistencia)
            @php
                $horaCompleta = Carbon::parse($asistencia->hora)->format('H:i:s');
                $esRetraso = $index === 0 && $evaluacion['estado'] === 'retraso';
                $device = 'Dispositivo registrado';
                if ($asistencia->id_equipo && ($data = json_decode($asistencia->id_equipo, true))) {
                    $agent = $data['agent'] ?? $data['userAgent'] ?? '';
                    $platform = $data['platform'] ?? '';
                    $browser = str_contains($agent, 'Firefox') ? 'Firefox' : (str_contains($agent, 'Edg/') ? 'Edge' : (str_contains($agent, 'Chrome') ? 'Chrome' : 'Navegador web'));
                    $system = preg_match('/Android/i', $agent) ? 'Android' : (preg_match('/iPhone|iPad|iPod/i', $agent) ? 'iOS' : (preg_match('/Windows/i', $agent) ? 'Windows' : ($platform ?: 'Equipo')));
                    $device = $system . ' · ' . $browser;
                }
            @endphp

            @if ($asistencia->registro_remoto)
                <div class="sia-attendance-cell__remote" x-data="{ open: false }">
                    <button type="button" class="sia-attendance-cell__link" x-on:click="open = true" @click.stop>
                        {{ $horaCompleta }} <span>(R)</span>
                    </button>

                    <div x-show="open" x-cloak x-transition.opacity class="sia-attendance-modal" @keydown.escape.window="open = false" style="display: none;">
                        <div class="sia-attendance-modal__backdrop" x-on:click="open = false"></div>
                        <section class="sia-attendance-modal__dialog" role="dialog" aria-modal="true" aria-label="Detalle de marcación remota" @click.stop>
                            <header class="sia-attendance-modal__header">
                                <div class="sia-attendance-modal__title">
                                    <span class="sia-attendance-modal__icon"><x-filament::icon icon="heroicon-m-map-pin" /></span>
                                    <div><h2>Marcación remota</h2><p>{{ $record->nombres }} {{ $record->apellidos }} · CI {{ $record->ci }}</p></div>
                                </div>
                                <button type="button" class="sia-attendance-modal__close" x-on:click="open = false" aria-label="Cerrar"><x-filament::icon icon="heroicon-m-x-mark" /></button>
                            </header>

                            <div class="sia-attendance-modal__body">
                                <div class="sia-attendance-modal__details">
                                    <div class="sia-attendance-modal__summary">
                                        <div><span>Fecha</span><strong>{{ Carbon::parse($asistencia->fecha)->translatedFormat('l, d F Y') }}</strong></div>
                                        <div><span>Hora registrada</span><strong>{{ $horaCompleta }}</strong></div>
                                    </div>
                                    <div class="sia-attendance-modal__field"><span>Equipo utilizado</span><strong>{{ $device }}</strong></div>
                                    <div class="sia-attendance-modal__field"><span>Ubicación registrada</span><code>{{ $asistencia->localizacion ?: 'No registrada' }}</code></div>
                                    <div class="sia-attendance-modal__note"><span>Justificación</span><p>{{ $asistencia->justificacion ?: 'No se registró una justificación.' }}</p></div>
                                </div>

                                <div class="sia-attendance-modal__map">
                                    @if ($asistencia->localizacion)
                                        <iframe title="Mapa de la marcación" src="https://maps.google.com/maps?q={{ urlencode($asistencia->localizacion) }}&z=16&output=embed" loading="lazy"></iframe>
                                        <span>Ubicación reportada por el dispositivo</span>
                                    @else
                                        <div class="sia-attendance-modal__map-empty"><x-filament::icon icon="heroicon-m-map-pin" /><strong>Ubicación no disponible</strong><p>Esta marcación no incluye coordenadas para mostrar el mapa.</p></div>
                                    @endif
                                </div>
                            </div>

                            <footer class="sia-attendance-modal__footer"><button type="button" x-on:click="open = false">Cerrar detalle</button></footer>
                        </section>
                    </div>
                </div>
            @elseif ($esRetraso)
                <span class="sia-attendance-cell--late">{{ $horaCompleta }}</span>
            @else
                <span>{{ $horaCompleta }}</span>
            @endif
        @endforeach
    </div>
@endif

<style>
 .sia-attendance-cell{text-align:center;font-size:.8rem;line-height:1.65}.sia-attendance-cell--rest{color:#15803d;font-weight:700}.sia-attendance-cell--schedule{color:#a16207;font-weight:700}.sia-attendance-cell--absence,.sia-attendance-cell--late{color:#dc2626;font-weight:750}.sia-attendance-cell--omission{display:block;color:#d97706;font-weight:750}.sia-attendance-cell__remote{display:inline}.sia-attendance-cell__link{padding:0;border:0;background:transparent;color:var(--sia-primary);font:inherit;font-weight:750;cursor:pointer}.sia-attendance-cell__link span{font-size:.65rem;font-weight:650}.sia-attendance-cell__link:hover{text-decoration:underline}.sia-attendance-modal{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:1.25rem}.sia-attendance-modal__backdrop{position:absolute;inset:0;background:rgb(15 23 42 / .64);backdrop-filter:blur(3px)}.sia-attendance-modal__dialog{position:relative;z-index:1;width:min(100%,68rem);max-height:calc(100vh - 2.5rem);overflow:auto;border:1px solid #cbd5e1;border-radius:1rem;background:#fff;color:#0f172a;box-shadow:0 1.5rem 4rem rgb(15 23 42 / .38)}.sia-attendance-modal__header{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;background:#f8fafc}.sia-attendance-modal__title{display:flex;align-items:center;gap:.75rem}.sia-attendance-modal__icon{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:.75rem;background:color-mix(in srgb,var(--sia-primary) 14%,#fff);color:var(--sia-primary)}.sia-attendance-modal__icon svg{width:1.3rem;height:1.3rem}.sia-attendance-modal h2{margin:0;color:#0f172a;font-size:1.05rem;font-weight:800}.sia-attendance-modal__title p{margin:.15rem 0 0;color:#64748b;font-size:.8rem}.sia-attendance-modal__close{display:grid;place-items:center;width:2rem;height:2rem;border:0;border-radius:.5rem;background:transparent;color:#64748b;cursor:pointer}.sia-attendance-modal__close:hover{background:#e2e8f0;color:#0f172a}.sia-attendance-modal__close svg{width:1.2rem;height:1.2rem}.sia-attendance-modal__body{display:grid;grid-template-columns:minmax(17rem,.85fr) minmax(21rem,1.15fr);gap:1.25rem;padding:1.5rem}.sia-attendance-modal__details{display:grid;align-content:start;gap:1rem}.sia-attendance-modal__summary{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.sia-attendance-modal__summary>div,.sia-attendance-modal__field,.sia-attendance-modal__note{padding:.85rem;border:1px solid #e2e8f0;border-radius:.75rem;background:#fff}.sia-attendance-modal__summary span,.sia-attendance-modal__field>span,.sia-attendance-modal__note>span{display:block;margin-bottom:.28rem;color:#64748b;font-size:.71rem;font-weight:750;text-transform:uppercase;letter-spacing:.04em}.sia-attendance-modal__summary strong,.sia-attendance-modal__field strong{display:block;color:#0f172a;font-size:.86rem;line-height:1.4}.sia-attendance-modal__field code{color:#2563eb;font-size:.78rem;overflow-wrap:anywhere}.sia-attendance-modal__note{background:#f8fafc}.sia-attendance-modal__note p{margin:0;color:#334155;font-size:.84rem;line-height:1.55}.sia-attendance-modal__map{display:grid;grid-template-rows:minmax(18rem,1fr) auto;overflow:hidden;border:1px solid #cbd5e1;border-radius:.85rem;background:#f1f5f9}.sia-attendance-modal__map iframe{width:100%;min-height:18rem;border:0}.sia-attendance-modal__map>span{padding:.55rem .75rem;color:#64748b;font-size:.72rem;background:#fff}.sia-attendance-modal__map-empty{display:grid;place-content:center;justify-items:center;min-height:18rem;padding:1.5rem;text-align:center;color:#64748b}.sia-attendance-modal__map-empty svg{width:2rem;height:2rem;margin-bottom:.6rem;color:var(--sia-primary)}.sia-attendance-modal__map-empty strong{color:#334155;font-size:.9rem}.sia-attendance-modal__map-empty p{max-width:15rem;margin:.35rem 0 0;font-size:.78rem;line-height:1.45}.sia-attendance-modal__footer{display:flex;justify-content:flex-end;padding:1rem 1.5rem;border-top:1px solid #e2e8f0;background:#f8fafc}.sia-attendance-modal__footer button{min-height:2.4rem;padding:.45rem .85rem;border:0;border-radius:.6rem;background:var(--sia-primary);color:#fff;font-size:.8rem;font-weight:750;cursor:pointer}.dark .sia-attendance-modal__dialog{border-color:#475569;background:#1e293b;color:#f8fafc}.dark .sia-attendance-modal__header,.dark .sia-attendance-modal__footer,.dark .sia-attendance-modal__note{border-color:#475569;background:#172033}.dark .sia-attendance-modal h2,.dark .sia-attendance-modal__summary strong,.dark .sia-attendance-modal__field strong,.dark .sia-attendance-modal__map-empty strong{color:#f8fafc}.dark .sia-attendance-modal__title p,.dark .sia-attendance-modal__summary span,.dark .sia-attendance-modal__field>span,.dark .sia-attendance-modal__note>span,.dark .sia-attendance-modal__map>span{color:#94a3b8}.dark .sia-attendance-modal__summary>div,.dark .sia-attendance-modal__field{border-color:#475569;background:#1e293b}.dark .sia-attendance-modal__note p{color:#cbd5e1}.dark .sia-attendance-modal__map{border-color:#475569;background:#172033}.dark .sia-attendance-modal__map>span{background:#1e293b}.dark .sia-attendance-modal__close{color:#94a3b8}.dark .sia-attendance-modal__close:hover{background:#334155;color:#fff}@media(max-width:760px){.sia-attendance-modal{padding:.65rem}.sia-attendance-modal__header{padding:1rem}.sia-attendance-modal__body{grid-template-columns:1fr;padding:1rem}.sia-attendance-modal__map{order:-1}.sia-attendance-modal__footer{padding:1rem}.sia-attendance-modal__summary{grid-template-columns:1fr}.sia-attendance-modal__dialog{max-height:calc(100vh - 1.3rem)}}
</style>
