@extends('errors.layout')
@section('code', '404')
@section('title', 'No encontramos esa página')
@section('message', 'El enlace pudo cambiar o la dirección está incompleta.')
@section('hint', 'Puedes volver al panel y continuar trabajando con normalidad.')
@section('actions')
    <a class="button primary" href="/dashboard">Ir al panel principal</a>
@endsection
