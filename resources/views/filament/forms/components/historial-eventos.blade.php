@php
    $ticket = $getRecord() ?? null;
    $eventos = $ticket?->eventosOrdenados ?? collect();
@endphp

<div class="space-y-3">
    @if($eventos->isEmpty())
        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-inbox class="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No hay historial de eventos</p>
        </div>
    @else
        <div class="space-y-2 max-h-96 overflow-y-auto pr-2">
            @foreach($eventos as $index => $evento)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-white dark:bg-gray-800">
                    <!-- Encabezado con número y fecha -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <!-- Número de evento -->
                            <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $loop->iteration }}
                            </div>
                            
                            <!-- Estado del evento -->
                            <div class="flex items-center gap-2">
                                @switch($evento->estado)
                                    @case('entrada')
                                        <x-heroicon-o-inbox class="w-5 h-5 text-blue-500" />
                                        <span class="text-blue-600 dark:text-blue-400 font-medium">Entrada</span>
                                        @break
                                    @case('pendiente')
                                        <x-heroicon-o-clock class="w-5 h-5 text-yellow-500" />
                                        <span class="text-yellow-600 dark:text-yellow-400 font-medium">Pendiente</span>
                                        @break
                                    @case('salida')
                                        <x-heroicon-o-arrow-up-tray class="w-5 h-5 text-red-500" />
                                        <span class="text-red-600 dark:text-red-400 font-medium">Salida</span>
                                        @break
                                    @case('cerrado')
                                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                                        <span class="text-green-600 dark:text-green-400 font-medium">Cerrado</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                        
                        <!-- Fecha y hora -->
                        <div class="text-right">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block">
                                {{ $evento->created_at->format('d/m/Y') }}
                            </span>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-medium">
                                {{ $evento->created_at->format('H:i:s') }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Información del evento -->
                    <div class="text-sm space-y-2">
                        <!-- Remitente y Destinatario -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @if($evento->remitente)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500 dark:text-gray-400">De:</span>
                                    <span class="font-medium truncate">{{ $evento->remitente->full_name }}</span>
                                </div>
                            @endif
                            
                            @if($evento->destinatario)
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500 dark:text-gray-400">Para:</span>
                                    <span class="font-medium truncate">{{ $evento->destinatario->full_name }}</span>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Fecha de entrada si existe -->
                        @if($evento->fecha_entrada && $evento->estado == 'entrada')
                            <div class="flex items-center gap-1 text-xs">
                                <span class="text-gray-500 dark:text-gray-400">Fecha entrada:</span>
                                <span class="font-medium">
                                    {{ \Carbon\Carbon::parse($evento->fecha_entrada)->format('d/m/Y H:i:s') }}
                                </span>
                            </div>
                        @endif
                        
                        <!-- Observaciones -->
                        @if($evento->observaciones)
                            <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-900 rounded">
                                <p class="text-gray-600 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ $evento->observaciones }}</p>
                            </div>
                        @endif

                        
                        
                        <!-- Prioridad si existe -->
                        @if($evento->prioridad)
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Prioridad:</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full 
                                    @switch($evento->prioridad)
                                        @case('urgente') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 @break
                                        @case('alta') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300 @break
                                        @case('media') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 @break
                                        @case('baja') bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 @break
                                        @default bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300
                                    @endswitch">
                                    {{ ucfirst($evento->prioridad) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Resumen del historial -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 dark:text-gray-400">Total de eventos:</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $eventos->count() }}</span>
                </div>
                <div class="text-gray-500 dark:text-gray-400 text-xs">
                    Ordenados cronológicamente
                </div>
            </div>
        </div>
    @endif
</div>