@extends('adminlte::page')

@section('title', 'Crear Hoja de Ruta')

@section('content_header')
    <h1 class="text-center"><strong>CREAR HOJA DE RUTA</strong> </h1>
@stop



@section('content')
    @if (session('info'))
        <div class="alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            {!! Form::open(['route' => 'correspondencia.hojaruta.store']) !!}
            <div class="form-group row">
                {{-- Formulario de creacion de tramite --}}
                <div class="col">
                    {!! Form::label('remitente', 'Remitente') !!}
                    {!! Form::select('remitente', [$remitente], true, ['class' => 'form-control','placeholder' => 'Seleccione el proceso']) !!}
                    @error('CI')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
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
            </div>

            {{-- Formulario de creacion de Hoja Ruta --}}
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
                    {!! Form::label('hr_interno', 'Interno') !!}
                    <input onclick="myFunction()" type='radio' value='0' name='hr_externo' id='hr_interno' onclick="myFunction()" checked>
                </div>
                <div class="col-auto">
                    {!! Form::label('hr_externo', 'Externo') !!}
                    <input onclick="myFunction()" type='radio' value='1' name='hr_externo' id='hr_externo' onclick="myFunction()">
                </div>
            </div>

            <div id="hr_interno1">
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
            </div>

            <div id="hr_externo1" style="display: none">
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
            </div>

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
                      @foreach ($hojaruta as $hojaruta)
                      <tr>
                        {{-- <td>{{$tramite->id}}</td> --}}
                        <td>{{$hojaruta->tramites()->remitente}}</td>
                        <td>{{$hojaruta->apellidos}}</td>
                        {{-- <td>{{$hojaruta->CI}}</td>
                        <td>{{$hojaruta->telefono_1}}</td>
                        <td>{{$hojaruta->email_personal}}</td>
                        <td>{{$hojaruta->fecha_nac}}</td>
                        <td width="10px"><button  type="button" class="btn btn-outline-success btn-sm openBtn" data-toggle="modal" data-target="#agregarEmpleado" title="Ver la informacion completa de la persona"><i class="fas fa-fw fa-eye"></i></button></td>
                        <td width="10px"><a href="{{route('empleado.personal.edit', $hojaruta)}}" class="btn btn-outline-warning btn-sm" ><i class="fas fa-fw fa-edit"></i></a></td> --}}

                        <td width="10px">
                            <form action="{{route('empleado.personal.destroy', $hojaruta)}}" method="POST" >
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
