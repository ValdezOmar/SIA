
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
            {!! Form::open(['route'=>'empleado.empleado.store']) !!}
                <div class="form-group row">
                    <div class="col">
                        {!! Form::label('personal_id', 'Personal') !!}
                        <div class="input-group col">
                            {!! Form::select('personal_id', [$personal], null, ['class' => 'form-control', 'placeholder' => 'Seleccione el personal nuevo']) !!}
                            <div class="input-group-append">
                            <a href="{{ route('empleado.personal.create') }}" class="input-group-text btn btn-success" title="Agregar nueva persona si no se encuentra en la lista"><i class="fas fa-user-plus"></i></a>
                            </div>
                        </div>
                    </div>


                    <div class="form-group col mt-5">
                        <div class="custom-control custom-switch">
                          <input onclick="myFunction()" type="checkbox" class="custom-control-input" id="activo">
                          <label class="custom-control-label" for="activo">Empleado Activo</label>
                        </div>
                      </div>
                </div>

                <div id="empleadoNuevo" style="display: none" name="empleadoNuevo">

                <div class="form-group row">
                    <div class="input-group col">
                        <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        {!! Form::email('email', null, ['class'=>'form-control', 'placeholder' => 'Ingrese el email corporativo']) !!}
                    </div>
                    <div class="input-group col">
                        <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        </div>
                        {!! Form::text('telefono_int', null, ['class'=>'form-control', 'placeholder' => 'Ingrese el numero del telefono interno (Si tiene)']) !!}
                    </div>
                </div>

                <div class="form-group row">


                    <div class="col">
                        {!! Form::label('fecha_baja', 'Fecha de baja del personal') !!}
                        {!! Form::date('fecha_baja', null, ['class' => 'form-control']) !!}
                    </div>

                    <div class="col">
                        {!! Form::label('fecha_cambio', 'Fecha de cambio de area') !!}
                        {!! Form::date('fecha_cambio', null, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col">
                        {!! Form::label('cargo_id', 'Cargo') !!}
                        {!! Form::select('cargo_id', [$cargo], null, ['class' => 'form-control', 'placeholder' => 'Seleccione el cargo']) !!}
                    </div>
                    <div class="col">
                        {!! Form::label('fecha_alta', 'Fecha de alta del empleado') !!}
                        {!! Form::date('fecha_alta', now(), ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-6">
                        {!! Form::label('password', 'Introduza el password') !!}
                        {!! Form::password('password', ['class'=>'form-control']) !!}
                        @error('password')
                        <span class="text-danger">{{$message}}</span>
                        @enderror
                    </div>
                </div>
                {!! Form::submit('Crear Personal', ['class'=>'btn btn-primary']) !!}
            </div>
            {!! Form::close() !!}
        </div>
    </div>
@stop

@section('js')
    <script>
        function myFunction() {
             var personalNuevo = document.getElementById("activo");
            if (personalNuevo.checked == false) {
                empleadoNuevo.style.display = "none";
                // document.getElementById('cite_externo').value = '';
                // document.getElementById('remitente_externo_id').value = '';

            } else {
                empleadoNuevo.style.display = "block";
            }
        }
    </script>
@stop





