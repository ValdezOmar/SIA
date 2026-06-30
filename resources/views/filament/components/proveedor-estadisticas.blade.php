@php
    $totalArticulos = $totalArticulos ?? 0;
    $articulosPrincipales = $articulosPrincipales ?? 0;
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Total de Artículos -->
    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-blue-600 font-medium">Total de Artículos</div>
                <div class="text-2xl font-bold text-blue-900">{{ $totalArticulos }}</div>
            </div>
            <div class="text-3xl text-blue-400">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div class="text-xs text-blue-500 mt-2">Artículos asociados a este proveedor</div>
    </div>
    
    <!-- Artículos Principales -->
    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-yellow-600 font-medium">Artículos Principales</div>
                <div class="text-2xl font-bold text-yellow-900">{{ $articulosPrincipales }}</div>
            </div>
            <div class="text-3xl text-yellow-400">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
        </div>
        <div class="text-xs text-yellow-500 mt-2">Artículos donde es proveedor principal</div>
    </div>
    
    <!-- Información Adicional -->
    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-green-600 font-medium">Estado</div>
                <div class="text-2xl font-bold text-green-900">
                    @if($record && $record->activo)
                        <span class="text-green-600">Activo</span>
                    @else
                        <span class="text-red-600">Inactivo</span>
                    @endif
                </div>
            </div>
            <div class="text-3xl text-green-400">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>
        <div class="text-xs text-green-500 mt-2">
            @if($record && $record->created_at)
                Registrado: {{ $record->created_at->format('d/m/Y') }}
            @endif
        </div>
    </div>
</div>