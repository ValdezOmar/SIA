@extends('errors.layout')
@section('code', '500')
@section('title', 'Algo no salió como esperábamos')
@section('message', 'El sistema encontró un inconveniente temporal. No necesitas hacer nada técnico: prueba nuevamente y, si continúa, comunícalo a soporte.')
@section('hint', 'Tus datos no se modificarán hasta que una operación termine correctamente.')
@section('accent', '#dc6b24')
@section('accent-soft', '#ffedd5')
@section('actions')
    <button type="button" class="button primary" onclick="window.location.reload()"><svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992V4.356m-1.291 5.21a8.25 8.25 0 1 0 .205 4.47" /></svg>Intentar nuevamente</button>
    <a class="button" href="{{ url('/dashboard') }}">Ir al panel</a>
@endsection
