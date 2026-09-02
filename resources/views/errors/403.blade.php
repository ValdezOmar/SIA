@extends('errors.layout')
@section('code', '403')
@section('title', 'Esta sección está protegida')
@section('message', 'Tu cuenta no tiene permiso para acceder a este contenido.')
@section('hint', 'Si necesitas acceso, comunícate con el responsable del sistema.')
@section('accent', '#7c3aed')
@section('accent-soft', '#ede9fe')
@section('actions')
    <a class="button primary" href="/dashboard">Ir al panel principal</a>
@endsection
