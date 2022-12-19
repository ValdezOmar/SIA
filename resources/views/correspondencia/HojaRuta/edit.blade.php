@extends('adminlte::page')

@section('title', 'Editar Hoja de Ruta')

@section('content_header')
    <h1 class="text-center"><strong>EDITAR HOJA DE RUTA</strong> </h1>
@stop



@section('content')
    @if (session('info'))
        <div class="alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif
    <div class="card">
        <div class="card-body">

            {!! Form::model($hojaruta, ['route' => ['correspondencia.hojaruta.update', $hojaruta], 'method' => 'put']) !!}

           

            {{-- Formulario de hoja de ruta --}}
            <div class="form-group row">
                <div class="col">
                    {!! Form::label('hoja_ruta', 'Numero de Hoja de Ruta') !!}
                    {!! Form::text('hoja_ruta', null, ['class' => 'form-control', 'placeholder' => 'Numero de Hoja de Ruta']) !!}
                    @error('hoja_ruta')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col">
                    {!! Form::label('fecha_ingreso', 'Fecha y hora de ingreso del documento*') !!}
                    {!! Form::datetimeLocal('fecha_ingreso', now(), ['class' => 'form-control']) !!}
                    @error('fecha_ingreso')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

           <strong class="mb-4">Tipo de Tramite</strong> <br>

            <div class="form-group row">
                <div class="col-auto">
                    {!! Form::checkbox('hr_externo', 'hr_externo', null) !!}
                    {!! Form::label('hr_interno', 'Interno') !!}
                    <input onclick="myFunction()" type='radio' value='0' name='hr_externo' id='hr_interno' onclick="myFunction()">
                </div>
                <div class="col-auto">
                    {!! Form::label('hr_externo', 'Externo') !!}
                    <input onclick="myFunction()" type='radio' value='1' name='hr_externo' id='hr_externo' onclick="myFunction()">
                </div>
            </div>
            @if ($hojaruta->hr_externo==0)

                    <div class="form-group row" >
                        <div class="col">
                            {!! Form::label('cite_interno_id', 'CITE Interno') !!}
                            {!! Form::select('cite_interno_id', [$cite], null, ['class' => 'form-control', 'placeholder' => 'Seleccione un CITE']) !!}
                        </div>

                        <div class="col">
                            {!! Form::label('remitente_interno_id', 'Remitente Interno') !!}
                            {!! Form::select('remitente_interno_id', [$remitenteInterno], null, ['class' => 'form-control', 'placeholder' => 'Seleccione el remitente interno']) !!}
                        </div>
                    </div>

            @else

                <div class="form-group row" >
                    <div class="col">
                        {!! Form::label('cite_externo', 'CITE Externo') !!}
                        {!! Form::text('cite_externo', null, ['class' => 'form-control', 'placeholder' => 'Llene el CITE del documento externo']) !!}
                        @error('apellidos')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col">
                        {!! Form::label('remitente_externo_id', 'Remitente Externo') !!}
                        {!! Form::select('remitente_externo_id', [$remitenteExterno], null, ['class' => 'form-control', 'placeholder' => 'Seleccione el remitente externo']) !!}
                    </div>
                </div>
            @endif

            <div class="col">
                {!! Form::label('tipo_proceso_id', 'Tipo de proceso') !!}
                {!! Form::select('tipo_proceso_id', [$tipoProceso], null, ['class' => 'form-control', 'placeholder' => 'Seleccione el proceso']) !!}
                @error('tipo_proceso_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                {!! Form::label('asunto', 'Asunto*') !!}
                {!! Form::textarea('asunto', null, ['class' => 'form-control', 'placeholder' => 'Llene el asunto del documento']) !!}
                @error('asunto')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {!! Form::submit('Crear Hoja de Ruta', ['class' => 'btn btn-primary']) !!}
        {!! Form::close() !!}
    </div>
    </div>
@stop

