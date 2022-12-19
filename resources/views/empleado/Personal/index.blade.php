@extends('adminlte::page')

@section('title', 'Personal')
@section('content_header')
    <h1>ADMINISTRAR PERSONAL</h1>
@stop

@section('content')
@if (session('info'))
<div class="alert alert-success">
    <strong>{{session('info')}}</strong>
</div>
@endif
<div class="card" >
    <div class="card-body">

        <a href="{{route('empleado.personal.create')}}" class="btn btn-secondary mb-2">Añadir Personal</a>

        <div class="table-responsive">
            <table class="table table-borderless table-hover">
                {{-- table-borderless --}}
              <thead class="table-warning">
                <tr>
                  <th >ID</th>
                  <th >NOMBRES</th>
                  <th >APELLIDOS</th>
                  <th >CI</th>
                  <th >TELEFONO</th>
                  <th >EMAIL</th>
                  <th >FECHA NAC</th>
                  <th colspan=3></th>

                </tr>
              </thead>
              <tbody>
                  @foreach ($personal as $personal)
                  <tr>
                    <td>{{$personal->id}}</td>
                    <td>{{$personal->nombres}}</td>
                    <td>{{$personal->apellidos}}</td>
                    <td>{{$personal->CI}}</td>
                    <td>{{$personal->telefono_1}}</td>
                    <td>{{$personal->email_personal}}</td>
                    <td>{{$personal->fecha_nac}}</td>
                    <td width="10px"><button  type="button" class="btn btn-outline-success btn-sm openBtn" data-toggle="modal" data-target="#agregarEmpleado" title="Ver la informacion completa de la persona"><i class="fas fa-fw fa-eye"></i></button></td>
                    <td width="10px"><a href="{{route('empleado.personal.edit', $personal)}}" class="btn btn-outline-warning btn-sm" ><i class="fas fa-fw fa-edit"></i></a></td>

                    <td width="10px">
                        <form action="{{route('empleado.personal.destroy', $personal)}}" method="POST" >
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-fw fa-trash"></i>
                            </button>

                        </form>
                    </td>
                    </tr>
                    @endforeach
              </tbody>
            </table>
          </div>
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
                {!! Form::model($personal, ['route'=>['empleado.personal.update', $personal], 'method'=>'put']) !!}
                <div class="form-group">
                    {!! Form::label('nombres', 'Nombres') !!}
                    {!! Form::text('nombres', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}
                @error('nombre')
                    <span class="text-danger">{{$message}}</span>
                @enderror
                </div>


                <div class="form-group">
                    {!! Form::label('apellidos', 'Apellidos') !!}
                    {!! Form::text('apellidos', null, ['class'=>'form-control', 'placeholder'=>'Ingrese los apellidos de la persona']) !!}
                </div>

                {!! Form::label('CI', 'CI') !!}
                {!! Form::text('CI', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('fecha_nac', 'fecha de nacimiento') !!}
                {!! Form::text('fecha_nac', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('telefono_1', 'telefono Personal') !!}
                {!! Form::text('telefono_1', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('telefono_2', 'telefono_2') !!}
                {!! Form::text('telefono_2', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('direccion', 'direccion') !!}
                {!! Form::text('direccion', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('email_personal', 'email_personal') !!}
                {!! Form::text('email_personal', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('descripcion', 'descripcion') !!}
                {!! Form::text('descripcion', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('adjunto', 'adjunto') !!}
                {!! Form::text('adjunto', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('foto', 'foto') !!}
                {!! Form::text('foto', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::submit('Crear Personal', ['class'=>'btn btn-primary']) !!}

            {!! Form::close() !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
            </div>
        </div>

</div>
    </div>
</div>
@stop


@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop


