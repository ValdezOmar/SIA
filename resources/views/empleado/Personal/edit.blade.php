
@extends('adminlte::page')

@section('title', 'Añadir personal')

@section('content_header')
    <h1>EDITAR DATOS DEL PERSONAL</h1>
@stop

@section('content')
    @if (session('info'))
        <div class="alert alert-success">
            <strong>{{session('info')}}</strong>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
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
    </div>
@stop




