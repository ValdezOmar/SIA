@extends('adminlte::page')

@section('title', 'Crear Hoja de Ruta')

@section('content_header')
    <h1 class="text-center"><strong>CREAR CITE</strong> </h1>
@stop
@section('content')
    @if (session('info'))
        <div class="alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            {!! Form::open(['route' => 'correspondencia.cite.store']) !!}

            <div >
                {!! Form::label('cite', 'CITE') !!}
                {!! Form::text('cite', null, ['class' => 'form-control', 'placeholder' => 'Numero de CITE']) !!}
            </div>

            <div class="form-group row">

                <div class="col">
                    {!! Form::label('cargo_id', 'Cargo') !!}
                    {!! Form::select('cargo_id', [$cargo], true, ['class' => 'form-control', 'readOnly']) !!}
                    @error('CI')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col">
                    {!! Form::label('elaborador_id', 'Elaboradir') !!}
                    {!! Form::select('elaborador_id', [$elaborador], true, ['class' => 'form-control', 'ReadOnly']) !!}
                    @error('CI')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div>
                {!! Form::label('asunto', 'Asunto') !!}
                {!! Form::textArea('asunto', null, ['class' => 'form-control', 'placeholder' => 'Asunto del CITE']) !!}
            </div>
        </div>

        {!! Form::submit('Crear Hoja de Ruta', ['class' => 'btn btn-primary']) !!}
        {!! Form::close() !!}
    </div>
    </div>
@stop

