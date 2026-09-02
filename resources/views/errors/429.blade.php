@extends('errors.layout')
@section('code', '429')
@section('title', 'Vamos un poco más despacio')
@section('message', 'Recibimos varias solicitudes seguidas. Espera unos segundos antes de continuar.')
@section('hint', 'No presiones varias veces un botón mientras la operación está procesándose.')
@section('accent', '#0284c7')
@section('accent-soft', '#e0f2fe')
@section('actions')
    <a class="button primary" href="/dashboard">Ir al panel principal</a>
@endsection
