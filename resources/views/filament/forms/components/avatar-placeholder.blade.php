@props([
    'nombres' => '',
    'apellidos' => ''
])

@php
    // Asegurarse de que los valores son strings
    $nombres = is_string($nombres) ? $nombres : '';
    $apellidos = is_string($apellidos) ? $apellidos : '';
    
    // Obtener iniciales de manera segura
    $inicialN = $nombres !== '' ? mb_substr($nombres, 0, 1) : '';
    $inicialA = $apellidos !== '' ? mb_substr($apellidos, 0, 1) : '';
    $iniciales = $inicialN . $inicialA;
@endphp

<div class="flex items-center justify-center w-full h-full" style="width: 350px; height: 350px;">
    @if($iniciales !== '')
        <div class="flex items-center justify-center w-full h-full rounded-full bg-blue-100 text-blue-600 font-bold text-4xl">
            {{ $iniciales }}
        </div>
    @else
        <img src="{{ asset('images/default-avatar.jpg') }}" 
             alt="Avatar por defecto" 
             class="w-full h-full rounded-full object-cover border-2 border-gray-200">
    @endif
</div>