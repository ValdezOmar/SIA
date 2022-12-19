@extends('adminlte::page')

@section('title', 'Personal')
@section('content_header')
    <h1>CREAR NUEVA HOJA DE RUTA</h1>
@stop

@section('content')
@if (session('info'))
<div class="alert alert-success">
    <strong>{{session('info')}}</strong>
</div>
@endif
<div class="card" >
    <div class="card-body">

        <a href="{{route('correspondencia.hojaruta.create')}}" class="btn btn-secondary mb-2">Añadir Personal</a>

        <div class="table-responsive">
            <table class="table table-borderless table-hover">
                {{-- table-borderless --}}
              <thead class="table-warning">
                <tr>
                  <th colspan></th>
                  <th colspan></th>
                  <th colspan></th>
                  <th colspan></th>
                  <th >ID</th>
                  <th >FECHA INGRESO</th>
                  <th >NUMERO HR</th>
                  <th >CITE INTERNO</th>
                  <th >CITE EXTERNO</th>
                  <th >TIPO PROCESO</th>
                  <th>REMITENTE INTERNO</th>
                  <th>REMITENTE EXTERNO</th>
                  <th >ASUNTO</th>
                  <th >EXTERNO</th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($hojaruta as $hojaruta)
                  <tr>
                    <td width="10px"><a href="{{route('correspondencia.bandeja.verTramite', $hojaruta)}}" class="btn btn-outline-primary btn-sm" ><i class="fas fa-arrow-right"></i></a></td>
                    <td width="10px"><a href="{{route('correspondencia.crearDerivacion', $hojaruta)}}" class="btn btn-outline-warning btn-sm" ><i class="fas fa-fw fa-eye"></i></a></td>
                    <td>{{$hojaruta->id}}</td>
                    <td>{{$hojaruta->fecha_ingreso}}</td>
                    <td>{{$hojaruta->hoja_ruta}}</td>
                    <td>{{$hojaruta->cite_interno}}</td>
                    <td>{{$hojaruta->cite_externo}}</td>
                    <td>{{$hojaruta->tipo_proceso}}</td>
                    <td>{{$hojaruta->full_name_interno}}</td>
                    <td>{{$hojaruta->full_name_externo}}</td>
                    <td>{{$hojaruta->asunto}}</td>
                    <td>{{$hojaruta->hr_externo}}</td>
                    </tr>
                    @endforeach
              </tbody>
            </table>
          </div>
     </div>
    </div>
</div>
<div class="modal fade" id="agregarEmpleado{{ $hojaruta}}" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content justify-content-center">
            <div class="modal-header">
                <h3 class="modal-title text-bold ">Agregar Empleado</h4>
                {{-- <button type="button" class="close width=10px" data-dismiss="modal">X</button> --}}

            </div>
            <div class="modal-body">
                {!! Form::model($hojaruta, ['route'=>['empleado.personal.update', $hojaruta], 'method'=>'put']) !!}
                <div class="form-group">
                    {!! Form::label('fecha_ingreso', 'fecha_ingreso') !!}
                    {!! Form::text('fecha_ingreso', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}
                @error('nombre')
                    <span class="text-danger">{{$message}}</span>
                @enderror
                </div>
                <div class="form-group">
                    {!! Form::label('hoja_ruta', 'hoja_ruta') !!}
                    {!! Form::text('hoja_ruta', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}
                @error('nombre')
                    <span class="text-danger">{{$message}}</span>
                @enderror
                </div>

                <div class="form-group">
                    {!! Form::label('cite_externo', 'cite_externo') !!}
                    {!! Form::text('cite_externo', null, ['class'=>'form-control', 'placeholder'=>'Ingrese los apellidos de la persona']) !!}
                </div>

                {!! Form::label('cite_externo', 'cite_externo') !!}
                {!! Form::text('cite_externo', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('tipo_proceso', 'tipo_proceso') !!}
                {!! Form::text('tipo_proceso', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('full_name_interno', 'full_name_interno') !!}
                {!! Form::text('full_name_interno', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('full_name_externo', 'full_name_externo') !!}
                {!! Form::text('full_name_externo', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('asunto', 'asunto') !!}
                {!! Form::text('asunto', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('hr_externo', 'hr_externo') !!}
                {!! Form::text('hr_externo', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::submit('Crear Personal', ['class'=>'btn btn-primary']) !!}

            {!! Form::close() !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>

</div
@stop


@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop



