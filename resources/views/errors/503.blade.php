@extends('errors.layout')
@section('code', '503')
@section('title', 'Volveremos en un momento')
@section('message', 'El sistema está recibiendo mantenimiento o se encuentra temporalmente ocupado. Intenta ingresar nuevamente dentro de unos minutos.')
@section('hint', 'Esta pausa es temporal y no afecta la información que ya está guardada.')
@section('accent', '#0f766e')
@section('accent-soft', '#ccfbf1')
@section('actions')
    <button type="button" class="button primary" onclick="window.location.reload()">Comprobar nuevamente</button>
@endsection
