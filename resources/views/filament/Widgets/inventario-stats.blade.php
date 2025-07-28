<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
    <div class="flex flex-col md:flex-row gap-4">
        {{-- Progreso del conteo --}}
        <div class="flex-1 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-medium text-gray-700 dark:text-gray-200">Progreso del Conteo</h3>
                <span class="text-lg font-bold {{ $porcentajeContados >= 90 ? 'text-green-600 dark:text-green-400' : ($porcentajeContados >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                    {{ $porcentajeContados }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2">
                <div 
                    class="h-3 rounded-full {{ $progressColor }}" 
                    style="width: {{ $porcentajeContados }}%"
                ></div>
            </div>
            <p class="text-sm text-gray-600 dark:text-white">
                {{ $itemsContados }} de {{ $totalItems }} ítems
            </p>
        </div>

        {{-- Contenedor de 3 estadísticas --}}
        <div class="flex-1 flex flex-row gap-3">
            {{-- Verificación física --}}
            <div class="flex-1 bg-green-50 dark:bg-green-50 p-3 rounded-lg border border-green-100 dark:border-green-800/50 flex flex-col items-center justify-center">
                <x-heroicon-o-check-badge class="w-5 h-5 text-green-600 dark:text-green-400 mb-1.5" />
                <p class="text-xs font-medium text-gray-700 dark:text-gray-700 mb-1">Verificados</p>
                <p class="text-sm font-bold text-gray-900 dark:text-gray-900">{{ $itemsContados }}</p>
            </div>

            {{-- Pendientes --}}
            <div class="flex-1 bg-red-50 dark:bg-red-50 p-3 rounded-lg border border-red-100 dark:border-red-800/50 flex flex-col items-center justify-center">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 mb-1.5" />
                <p class="text-xs font-medium text-gray-700 dark:text-gray-700 mb-1">Pendientes</p>
                <p class="text-sm font-bold text-gray-900 dark:text-gray-900">{{ $itemsNoContados }}</p>
            </div>

            {{-- Discrepancias --}}
            <div class="flex-1 bg-amber-50 dark:bg-amber-50 p-3 rounded-lg border border-amber-100 dark:border-amber-800/50 flex flex-col items-center justify-center">
                <x-heroicon-o-scale class="w-5 h-5  text-red-600 dark:text-red-400 mb-1.5" />
                <p class="text-xs font-medium text-gray-700 dark:text-gray-700 mb-1">Diferencias</p>
                <p class="text-sm font-bold text-gray-900 dark:text-gray-900">
                    {{ $diferenciaTotal }}
                </p>
            </div>
        </div>
    </div>
</div>