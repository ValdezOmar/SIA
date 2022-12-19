@extends('adminlte::page')

@section('title', 'Personal')
@section('content_header')
    <h1>LISTA DE CITES</h1>
@stop

@section('content')
@if (session('info'))
<div class="alert alert-success">
    <strong>{{session('info')}}</strong>
</div>
@endif
<div class="card" >
    <div class="card-body">

        <a href="{{route('correspondencia.cite.create')}}" class="btn btn-secondary mb-2">Añadir Cite</a>

        <div class="table-responsive">
            <table class="table table-borderless table-hover">
                {{-- table-borderless --}}
              <thead class="table-warning">
                <tr>
                  <th class="colspan"></th>
                  <th class="colspan"></th>
                  <th class="colspan"></th>
                  <th >ID</th>
                  <th >CITE</th>
                  <th >ASUNTO</th>
                  <th >CARGO</th>
                  <th >ELABORADOR</th>
                  <th >FECHA</th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($cite as $cite)
                  <tr>
                    <td width="10px"><button  type="button" class="btn btn-outline-success btn-sm openBtn" data-toggle="modal" data-target="#agregarEmpleado" title="Ver la informacion completa de la persona"><i class="fas fa-fw fa-eye"></i></button></td>
                    <td width="10px"><a href="{{route('empleado.personal.edit', $cite)}}" class="btn btn-outline-warning btn-sm" ><i class="fas fa-fw fa-edit"></i></a></td>

                    <td width="10px">
                        <form action="{{route('empleado.personal.destroy', $cite)}}" method="POST" >
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-fw fa-trash"></i>
                            </button>

                        </form>
                    </td>
                    <td>{{$cite->id}}</td>
                    <td>{{$cite->cite}}</td>
                    <td>{{$cite->asunto}}</td>
                    <td>{{$cite->cargo}}</td>
                    <td>{{$cite->full_name}}</td>
                    <td>{{$cite->created_at}}</td>
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
                {!! Form::model($cite, ['route'=>['empleado.personal.update', $cite], 'method'=>'put']) !!}
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


