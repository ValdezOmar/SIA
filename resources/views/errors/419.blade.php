@extends('errors.layout')
@section('code', '419')
@section('title', 'Tu sesión necesita renovarse')
@section('message', 'Por seguridad cerramos las sesiones que permanecen inactivas. Inicia sesión nuevamente y podrás continuar con normalidad.')
@section('hint', 'Si estabas completando un formulario, verifica la información antes de volver a guardarla.')
@section('accent', '#d97706')
@section('accent-soft', '#fef3c7')
@section('actions')
    <a class="button primary" href="{{ url('/dashboard/login') }}"><svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>Iniciar sesión</a>
@endsection
