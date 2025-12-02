@php
    /** @var \App\Models\HelpDesk\Ticket $record */
    $record = $getRecord();
@endphp

<div class="space-y-1">
    <div class="flex items-center gap-1">
        @switch($record->tipo)
            @case('preventivo')
                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full">🛡️Preventivo</span>
            @break
            @case('correctivo')
                <span class="text-xs px-2 py-0.5 bg-red-100 text-red-800 rounded-full">🔧Correctivo</span>
            @break
        @endswitch
        
        @switch($record->estado)
            @case('abierto')
                <span class="text-xs px-2 py-0.5 bg-green-100 text-green-800 rounded-full">Abierto</span>
            @break
            @case('en_proceso')
                <span class="text-xs px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full">En Proceso</span>
            @break
            @case('pendiente')
                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full">Pendiente</span>
            @break
            @case('cerrado')
                <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-800 rounded-full">Cerrado</span>
            @break
        @endswitch
    </div>
    
    <div class="text-xs text-gray-500">
        Creado: {{ $record->created_at?->format('d/m H:i') }}
    </div>
    
    <div class="text-xs text-gray-600 truncate" title="{{ $record->diagnostico ?? '' }}">
        {{ Str::limit($record->diagnostico ?? 'Sin diagnóstico', 40) }}
    </div>
    
    
</div>