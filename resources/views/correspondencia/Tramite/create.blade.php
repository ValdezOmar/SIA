@extends('adminlte::page')

@section('title', 'Crear Hoja de Ruta')

@section('content_header')
    <h1 class="text-center"><strong>PRIMERA DERIVACION</strong> </h1>
@stop

@section('content')
    @if (session('info'))
        <div class="alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            {!! Form::open(['route' => 'correspondencia.tramite.store']) !!}
            <div class="col">
                {!! Form::label('hoja_ruta_id', 'remitente') !!}
                {!! Form::select('hoja_ruta_id', [$hojaruta], ['class' => 'form-control', 'placeholder' => 'Numero de Hoja de Ruta']) !!}
            </div>

            <div class="form-group row">

                <div class="col">
                    {!! Form::label('remitente', 'remitente') !!}
                    {!! Form::text('remitente', $remitente, ['class' => 'form-control', 'placeholder' => 'Numero de Hoja de Ruta']) !!}

                </div>
                <div class="col">
                    {!! Form::label('destinatario', 'Destinatario') !!}
                    {!! Form::select('destinatario', [$destinatario], null, [
                        'class' => 'form-control',
                        'placeholder' => 'Seleccione el proceso',
                    ]) !!}
                    @error('CI')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col">
                    {!! Form::label('proveido', 'Proveido') !!}
                    {!! Form::text('proveido', null, ['class' => 'form-control', 'placeholder' => 'Numero de Hoja de Ruta']) !!}

                </div>

                <div class="col">
                    {!! Form::label('fecha_recepcion', 'fecha_recepcion') !!}
                    {!! Form::datetimeLocal('fecha_recepcion', now(), ['class' => 'form-control']) !!}

                </div>

                <div class="col">
                    {!! Form::label('fecha_derivacion', 'fecha_derivacion') !!}
                    {!! Form::datetimeLocal('fecha_derivacion', null, ['class' => 'form-control']) !!}

                </div>

                <div class="col">
                    {!! Form::label('fecha_rechazo', 'fecha_rechazo') !!}
                    {!! Form::datetimeLocal('fecha_rechazo', null, ['class' => 'form-control']) !!}

                </div>

                <div class="col">
                    {!! Form::label('fecha_anulado', 'fecha_anulado') !!}
                    {!! Form::datetimeLocal('fecha_anulado', null, ['class' => 'form-control']) !!}

                </div>

                <div class="col">
                    {!! Form::label('fecha_archivo', 'fecha_archivo') !!}
                    {!! Form::datetimeLocal('fecha_archivo', null, ['class' => 'form-control']) !!}

                </div>


            </div>

            <div class="form-group row" id="hr_interno1">



                <div class="col-auto">
                    {!! Form::label('estado', 'Estado') !!}
                    {!! Form::select('estado', [$estado], 1, [
                        'class' => 'form-control',
                        'placeholder' => 'Seleccione el remitente interno',
                ])!!}

                </div>

            </div>




            </div>
        </div>

        {!! Form::submit('Derivar', ['class' => 'btn btn-primary']) !!}

        {!! Form::close() !!}
    </div>
    </div>
@stop

@section('js')
    <script>
        function myFunction() {
            var hr_interno = document.getElementById("hr_interno");
            var hr_externo = document.getElementById("hr_externo");
            if (hr_externo.checked == true) {
                hr_externo1.style.display = "block";
                hr_interno1.style.display = "none";
                document.getElementById('cite_interno_id').value = '';
                document.getElementById('remitente_interno_id').value = '';
            } else {
                hr_externo1.style.display = "none";
                hr_interno1.style.display = "block";
                document.getElementById('cite_externo').value = '';
                document.getElementById('remitente_externo_id').value = '';
            }
        }
    </script>
@stop
