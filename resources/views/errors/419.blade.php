@extends('errors.layout')
@section('code', '419')
@section('title', 'Tu sesión terminó')
@section('message', 'Por seguridad, cerramos las sesiones que permanecen inactivas.')
@section('hint', 'Vuelve a ingresar para continuar. Revisa el formulario antes de guardarlo nuevamente.')
@section('accent', '#d97706')
@section('accent-soft', '#fef3c7')
@section('actions')
    <a class="button primary" href="/dashboard">Volver a ingresar</a>
@endsection
