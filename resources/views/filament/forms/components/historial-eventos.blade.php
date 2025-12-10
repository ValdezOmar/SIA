@php
    use Illuminate\Support\Facades\Storage;
    
    $ticket = $getRecord() ?? null;
    $eventos = $ticket?->eventosOrdenados ?? collect();
    $totalEventos = $eventos->count();
@endphp

<div class="space-y-3">
    @if($eventos->isEmpty())
        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-inbox class="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No hay historial de eventos registrados</p>
        </div>
    @else
        <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
            @foreach($eventos as $evento)
                @php
                    $numero = $totalEventos - $loop->index;
                @endphp
                
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                    <!-- Header con número y estado -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <!-- Número de evento -->
                            <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 text-xs font-bold text-blue-800 dark:text-blue-300">
                                {{ $numero }}
                            </div>
                            
                            <!-- Estado con icono y color de texto -->
                            <div class="flex items-center gap-2">
                                @switch($evento->estado)
                                    @case('entrada')
                                        <x-heroicon-o-inbox class="w-4 h-4" />
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">Entrada</span>
                                        @break
                                    @case('pendiente')
                                        <x-heroicon-o-clock class="w-4 h-4" />
                                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Pendiente</span>
                                        @break
                                    @case('atendido')
                                        <x-heroicon-o-check-circle class="w-4 h-4" />
                                        <span class="text-green-600 dark:text-green-400 font-medium">Atendido</span>
                                        @break
                                    @case('salida')
                                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                                        <span class="text-red-600 dark:text-red-400 font-medium">Salida</span>
                                        @break
                                    @case('cerrado')
                                        <x-heroicon-o-lock-closed class="w-4 h-4" />
                                        <span class="text-gray-600 dark:text-gray-400 font-medium">Cerrado</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        
                        <!-- Fecha de creación -->
                        <div class="text-right">
                            <!-- Prioridad con color de texto -->
                        @if($evento->prioridad)
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-flag class="w-3 h-3 text-gray-400 dark:text-gray-500" />
                                <div>
                                    
                                    <p class="text-xs font-bold 
                                        @switch($evento->prioridad)
                                            @case('urgente') text-red-600 dark:text-red-400 @break
                                            @case('alta') text-orange-600 dark:text-orange-400 @break
                                            @case('media') text-yellow-600 dark:text-yellow-400 @break
                                            @case('baja') text-gray-600 dark:text-gray-400 @break
                                            @default text-gray-600 dark:text-gray-400
                                        @endswitch">
                                        {{ ucfirst($evento->prioridad) }}
                                    </p>
                                </div>
                            </div>
                        @endif
                        </div>
                    </div>
                    
                    <!-- Contenido del evento -->
                    <div class="space-y-3 text-sm">
                        <!-- Personas involucradas -->
                        @if($evento->remitente || $evento->destinatario)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @if($evento->remitente)
                                    <div class="flex items-start gap-2">
                                        <x-heroicon-o-user class="w-3 h-3 mt-0.5 text-gray-400 dark:text-gray-500" />
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Remitente</p>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $evento->remitente->full_name }}</p>
                                        </div>
                                    </div>
                                @endif
                                
                                @if($evento->destinatario)
                                    <div class="flex items-start gap-2">
                                        <x-heroicon-o-user-circle class="w-3 h-3 mt-0.5 text-gray-400 dark:text-gray-500" />
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Destinatario</p>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $evento->destinatario->full_name }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        <!-- Fechas del evento con iconos -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @if($evento->fecha_entrada)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-arrow-down-tray class="w-3 h-3 text-blue-500" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Entrada</p>
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ \Carbon\Carbon::parse($evento->fecha_entrada)->format('d/m/Y H:i:s') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            
                            @if($evento->fecha_recepcion)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-hand-thumb-up class="w-3 h-3 text-green-500" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Recepción</p>
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ \Carbon\Carbon::parse($evento->fecha_recepcion)->format('d/m/Y H:i:s') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                            
                            @if($evento->fecha_salida)
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-arrow-up-tray class="w-3 h-3 text-red-500" />
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Derivado</p>
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ \Carbon\Carbon::parse($evento->fecha_salida)->format('d/m/Y H:i:s') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        
                        
                        <!-- Observaciones -->
                        @if($evento->observaciones)
                            <div class="mt-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-chat-bubble-left-right class="w-3 h-3 text-gray-400 dark:text-gray-500" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Observaciones</p>
                                </div>
                                <div class="pl-5">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-gray-900/30 p-3 rounded">
                                        {{ $evento->observaciones }}
                                    </p>
                                </div>
                            </div>
                        @endif
                        
                        <!-- Adjuntos clickeables -->
                        @if($evento->adjunto_remitente)
                            @php
                                // Manejo de adjuntos
                                $adjuntos = [];
                                
                                if (is_array($evento->adjunto_remitente)) {
                                    $adjuntos = $evento->adjunto_remitente;
                                } elseif (is_string($evento->adjunto_remitente) && json_decode($evento->adjunto_remitente, true)) {
                                    $adjuntos = json_decode($evento->adjunto_remitente, true);
                                } elseif (is_string($evento->adjunto_remitente) && !empty($evento->adjunto_remitente)) {
                                    $adjuntos = [$evento->adjunto_remitente];
                                }
                            @endphp
                            
                            @if(count($adjuntos) > 0)
                                <div class="mt-2">
                                    <div class="flex items-center gap-2 mb-2">
                                        <x-heroicon-o-paper-clip class="w-3 h-3 text-gray-400 dark:text-gray-500" />
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Archivos adjuntos</p>
                                    </div>
                                    <div class="pl-5 grid grid-cols-1 gap-1">
                                        @foreach($adjuntos as $adjunto)
                                            @php
                                                if (is_array($adjunto)) {
                                                    $ruta = $adjunto['path'] ?? $adjunto['url'] ?? $adjunto;
                                                    $nombre = $adjunto['name'] ?? basename($ruta);
                                                } else {
                                                    $ruta = $adjunto;
                                                    $nombre = basename($adjunto);
                                                }
                                                
                                                $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                                                
                                                // Determinar icono por extensión
                                                $icon = match($ext) {
                                                    'pdf' => 'heroicon-o-document-text',
                                                    'doc', 'docx' => 'heroicon-o-document',
                                                    'xls', 'xlsx' => 'heroicon-o-table-cells',
                                                    'jpg', 'jpeg', 'png', 'gif' => 'heroicon-o-photo',
                                                    default => 'heroicon-o-document',
                                                };
                                                
                                                // Determinar color por extensión
                                                $color = match($ext) {
                                                    'pdf' => 'text-red-500',
                                                    'doc', 'docx' => 'text-blue-500',
                                                    'xls', 'xlsx' => 'text-green-500',
                                                    'jpg', 'jpeg', 'png', 'gif' => 'text-purple-500',
                                                    default => 'text-gray-500',
                                                };
                                            @endphp
                                            
                                            <a href="{{ Storage::url($ruta) }}" 
                                               target="_blank" 
                                               class="group flex items-center gap-2 p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                <x-dynamic-component :component="$icon" class="w-4 h-4 {{ $color }}" />
                                                <span class="text-xs text-gray-700 dark:text-gray-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate">
                                                    {{ $nombre }}
                                                </span>
                                                <span class="text-xs text-gray-400 ml-auto">
                                                    .{{ strtoupper($ext) }}
                                                </span>
                                                <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3 text-gray-400 group-hover:text-blue-500" />
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Footer resumen -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-bars-3-bottom-left class="w-4 h-4 text-gray-400" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $totalEventos }}</span> eventos registrados
                    </span>
                </div>
                
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-arrow-path class="w-3 h-3" />
                       
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>