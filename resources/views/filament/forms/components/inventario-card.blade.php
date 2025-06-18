@php
    $record = $this->getRecord();
@endphp
@if($record && is_object($record))
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700 transition-all hover:shadow-xl">
    <!-- Encabezado con gradiente (se mantiene igual en ambos modos) -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4">
        <h2 class="text-xl font-bold text-center text-blue-600 dark:text-blue-400">{{ $record->descripcion ?? 'Producto sin descripción' }}</h2>
    </div>
    
    <!-- Contenido principal -->
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Código SIMEC -->
            <div class="flex items-start space-x-3">
                <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Código SIMEC</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->codigo ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- N° de Lote -->
            <div class="flex items-start space-x-3">
                <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° de Lote</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->lote ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Código Alterno -->
            <div class="flex items-start space-x-3">
                <div class="bg-purple-100 dark:bg-purple-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cód. Alterno</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->codigo_alterno ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Presentación -->
            <div class="flex items-start space-x-3">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Presentación</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->presentacion ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Unidad -->
            <div class="flex items-start space-x-3">
                <div class="bg-red-100 dark:bg-red-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unidad</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $record->unidad ?? 'N/A' }}</p>
                </div>
            </div>

            <!-- Vencimiento -->
            <div class="flex items-start space-x-3">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vencimiento</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">
                        @isset($record->fecha_ven)
                            {{ $record->fecha_ven->format('d/m/Y') }}
                        @else
                            Sin fecha
                        @endisset
                    </p>
                </div>
            </div>
        </div>

        <!-- Almacén y Empresa -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Almacén -->
            <div class="flex items-start space-x-3">
                <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Almacén</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium">
                        {{ $record->cod_almacen ?? '' }} - {{ $record->nombre_almacen ?? 'N/A' }}
                    </p>
                </div>
            </div>
            
            <!-- Empresa -->
            <div class="flex items-start space-x-3">
                <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Empresa</p>
                    <p class="text-gray-900 dark:text-gray-100 font-medium break-words">
                        {{ $record->empresa ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-red-700 dark:text-red-300 text-center">
    <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    No se pudo cargar la información del inventario
</div>
@endif