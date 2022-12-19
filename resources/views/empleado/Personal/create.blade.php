
@extends('adminlte::page')

@section('title', 'Añadir personal')

@section('content_header')
    <h1 class="text-center"><strong>AÑADIR PERSONAL NUEVO</strong> </h1>
@stop

@section('content')
    @if (session('info'))
    <div class="alert alert-success">
        <strong>{{session('info')}}</strong>
    </div>
    @endif
    <div class="card">
        <div class="card-body">
            {!! Form::open(['route'=>'empleado.personal.store']) !!}
                <div class="form-group row">
                    <div class="col">
                        {!! Form::label('nombres', 'Nombres') !!}
                        {!! Form::text('nombres', null, ['class'=>'form-control', 'placeholder'=>'Ingrese los nombres']) !!}
                    @error('nombres')
                        <span class="text-danger">{{$message}}</span>
                    @enderror
                    </div>
                    <div class="col">
                        {!! Form::label('apellidos', 'Apellidos') !!}
                        {!! Form::text('apellidos', null, ['class'=>'form-control', 'placeholder'=>'Ingrese los apellidos']) !!}
                        @error('apellidos')
                        <span class="text-danger">{{$message}}</span>
                    @enderror
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col">
                        {!! Form::label('CI', 'Carnet de Identidad') !!}
                        {!! Form::text('CI', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el CI o DNI']) !!}
                        @error('CI')
                        <span class="text-danger">{{$message}}</span>
                    @enderror
                    </div>
                    <div class="col">
                        {!! Form::label('fecha_nac', 'fecha de Nacimiento') !!}
                        {!! Form::text('fecha_nac', null, ['class'=>'form-control', 'placeholder'=>'Fecha de nacimiento']) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col">
                        {!! Form::label('telefono_1', 'Telefono Personal') !!}
                        {!! Form::text('telefono_1', null, ['class'=>'form-control', 'placeholder'=>'Telefono principal de contacto']) !!}

                    </div>
                    <div class="col">
                        {!! Form::label('telefono_2', 'Telefono Fijo') !!}
                        {!! Form::text('telefono_2', null, ['class'=>'form-control', 'placeholder'=>'Telefono fijo de contacto']) !!}

                    </div>
                </div>

                <div class="form-group row">
                    <div class="col">
                        {!! Form::label('direccion', 'Direccion Actual') !!}
                        {!! Form::text('direccion', null, ['class'=>'form-control', 'placeholder'=>'Diraccion actual donde vive']) !!}

                    </div>
                    <div class="col">
                        {!! Form::label('email_personal', 'Email Personal') !!}
                        {!! Form::text('email_personal', null, ['class'=>'form-control', 'placeholder'=>'Email personal de contacto']) !!}

                    </div>
                </div>

                <div class="form-group">
                        {!! Form::label('descripcion', 'Descripcion Personal') !!}
                        {!! Form::textarea('descripcion', null, ['class'=>'form-control', 'placeholder'=>'Describa brevemente su perfil personal profesional']) !!}

                    </div>
                </div>

                {!! Form::label('adjunto', 'adjunto') !!}
                {!! Form::text('adjunto', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::label('foto', 'foto') !!}
                {!! Form::text('foto', null, ['class'=>'form-control', 'placeholder'=>'Ingrese el nombre de la persona']) !!}

                {!! Form::submit('Crear Personal', ['class'=>'btn btn-primary']) !!}

            {!! Form::close() !!}
        </div>
    </div>
@stop




