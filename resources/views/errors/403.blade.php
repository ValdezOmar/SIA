@extends('errors.layout')
@section('code', '403')
@section('title', 'Esta sección está protegida')
@section('message', 'Tu cuenta no tiene permiso para acceder a este contenido. Puedes volver al panel o solicitar acceso al responsable del sistema.')
@section('hint', 'Si crees que deberías tener acceso, indica a soporte qué sección intentabas abrir.')
@section('accent', '#7c3aed')
@section('accent-soft', '#ede9fe')
@section('actions')
    <a class="button primary" href="{{ url('/dashboard') }}">Ir al panel principal</a>
@endsection
