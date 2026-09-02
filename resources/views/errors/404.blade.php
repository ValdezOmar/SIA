@extends('errors.layout')
@section('code', '404')
@section('title', 'No encontramos esa página')
@section('message', 'Puede que el enlace haya cambiado o que la dirección esté incompleta. Tus datos están seguros; puedes regresar y continuar trabajando.')
@section('hint', 'Revisa la dirección escrita o vuelve al panel principal para encontrar la sección que necesitas.')
@section('actions')
    <a class="button primary" href="{{ url('/dashboard') }}"><svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" /></svg>Ir al panel principal</a>
@endsection
