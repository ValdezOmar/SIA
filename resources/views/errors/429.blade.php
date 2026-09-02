@extends('errors.layout')
@section('code', '429')
@section('title', 'Vamos un poco más despacio')
@section('message', 'Recibimos varias solicitudes en muy poco tiempo. Espera unos segundos antes de intentarlo nuevamente.')
@section('hint', 'Evita presionar varias veces el botón mientras una operación está procesándose.')
@section('accent', '#0284c7')
@section('accent-soft', '#e0f2fe')
@section('actions')
    <button type="button" class="button primary" onclick="window.location.reload()">Intentar nuevamente</button>
@endsection
