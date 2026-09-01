@props([
    'nombres' => '',
    'apellidos' => '',
])

@php
    // Este componente se conserva para otras vistas que necesiten iniciales.
    $nombres = is_string($nombres) ? $nombres : '';
    $apellidos = is_string($apellidos) ? $apellidos : '';
    $inicialN = $nombres !== '' ? mb_substr($nombres, 0, 1) : '';
    $inicialA = $apellidos !== '' ? mb_substr($apellidos, 0, 1) : '';
    $iniciales = $inicialN.$inicialA;
@endphp

<div class="flex h-full w-full items-center justify-center" style="width: 350px; height: 350px;">
    @if ($iniciales !== '')
        <div class="flex h-full w-full items-center justify-center rounded-full bg-blue-100 text-4xl font-bold text-blue-600">
            {{ $iniciales }}
        </div>
    @else
        <img
            src="{{ asset('images/default-avatar.jpg') }}"
            alt="Avatar predeterminado"
            class="h-full w-full rounded-full border-2 border-gray-200 object-cover"
        >
    @endif
</div>
