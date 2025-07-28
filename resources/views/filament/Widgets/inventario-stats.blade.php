<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Progreso del conteo --}}
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-medium text-gray-700 dark:text-gray-300">Progreso del Conteo</h3>
                <span class="text-lg font-bold {{ $porcentajeContados >= 90 ? 'text-green-500' : ($porcentajeContados >= 50 ? 'text-yellow-500' : 'text-red-500') }}">
                    {{ $porcentajeContados }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-4 mb-2">
                <div 
                    class="h-4 rounded-full {{ $progressColor }}" 
                    style="width: {{ $porcentajeContados }}%"
                ></div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $itemsContados }} de {{ $totalItems }} ítems contados
            </p>
        </div>        

    {{-- Verificados, Pendientes y Discrepancias --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 text-center">
        {{-- Verificación física --}}
        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
            <div class="flex flex-col items-center">
                <x-heroicon-o-check-badge class="w-5 h-5 text-green-500 mb-1" />
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Verificados</p>
                <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $itemsContados }}</p>
            </div>
        </div>

        {{-- Pendientes --}}
        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
            <div class="flex flex-col items-center">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500 mb-1" />
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Pendientes</p>
                <p class="text-sm font-bold text-gray-800 dark:text-white">{{ $itemsNoContados }}</p>
            </div>
        </div>

        {{-- Discrepancias --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg">
            <div class="flex flex-col items-center">
                <x-heroicon-o-scale class="w-5 h-5 {{ $diferenciaTotal == 0 ? 'text-green-500' : 'text-amber-500' }} mb-1" />
                <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Diferencias</p>
                <p class="text-sm font-bold {{ $diferenciaTotal == 0 ? 'text-green-500' : 'text-amber-500' }}">
                    {{ $diferenciaTotal }}
                </p>
            </div>
        </div>
    </div>
</div>
