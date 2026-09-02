@extends('errors.layout')
@section('code', '500')
@section('title', 'Algo no salió como esperábamos')
@section('message', 'El sistema encontró un inconveniente temporal. Tus datos no se modificarán hasta que la operación termine correctamente.')
@section('hint', 'Si continúa ocurriendo, informa a soporte qué estabas intentando hacer.')
@section('accent', '#dc6b24')
@section('accent-soft', '#ffedd5')
@section('actions')
    <a class="button primary" href="/dashboard">Ir al panel principal</a>
@endsection
