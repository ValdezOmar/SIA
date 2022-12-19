@extends('adminlte::page')

@section('title', 'Empleados')

@section('content_header')
    <h1>LISTA EMPLEADOS</h1>
@stop

@section('content')
@if (session('info'))
<div class="alert alert-success">
    <strong>{{session('info')}}</strong>
</div>
@endif

<div class="card ml-0" >
    <div class="card-body">

        {{-- <button type="button" class="btn btn-secondary openBtn" data-toggle="modal" data-target="#agregarEmpleado">Nuevo Empleado</button> --}}
        <a href="{{ route('empleado.empleado.create') }}" class="btn btn-primary">Agregar Empleado</a>

        <div class="table-responsive">
            <table class="table table-borderless mb-0 ml-0">
              <thead >
                <tr>
                  <th  class="colspan"></th>
                  <th  class="colspan"></th>N
                  <th >ID</th>
                  <th >NOMBRES</th>
                  <th >APELLIDOS</th>
                  <th >FECHA NACIMIENTO</th>
                  <th >CARGO</th>
                  <th >UNIDAD</th>
                  <th >TELEFONO</th>
                  <th >INTERNO</th>
                  <th >EMAIL</th>
                  <th >FECHA</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($empleado as $empleado)
                <tr>
                    <td><a href="" class="btn btn-success mb-2"><i class="fas fa-fw fa-eye"></i></td>
                    <td></a><a href="" class="btn btn-success"><i class="fas fa-fw fa-edit"></i></a></td>
                    <td>{{$empleado->id}}</td>
                    <td>{{$empleado->nombres}}</td>
                    <td>{{$empleado->apellidos}}</td>
                    <td>{{$empleado->fecha_nac}}</td>
                    <td>{{$empleado->cargo}}</td>
                    <td>{{$empleado->unidad}}</td>
                    <td>{{$empleado->telefono_1}}</td>
                    <td>{{$empleado->telefono_2}}</td>
                    <td>{{$empleado->email}}</td>
                    <td>{{$empleado->fecha_alta}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>


<!-- Modal -->
<div class="modal fade" id="agregarEmpleado" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content justify-content-center">
            <div class="modal-header">
                <h3 class="modal-title text-bold ">Agregar Empleado</h4>
                {{-- <button type="button" class="close width=10px" data-dismiss="modal">X</button> --}}

            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>
    </div>
</div>
@stop


@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop


