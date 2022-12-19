@extends('adminlte::page')

@section('title', 'Personal')
@section('content_header')
    <h1>BUSCAR TRAMITES </h1>
@stop

@section('content')
@if (session('info'))
<div class="alert alert-success">
    <strong>{{session('info')}}</strong>
</div>
@endif
<div class="card" >
    <div class="card-body">

        <a href="{{route('correspondencia.tramite.create')}}" class="btn btn-secondary mb-2">Añadir Personal</a>

        <div class="table-responsive">
            <table class="table table-borderless table-hover">
                {{-- table-borderless --}}
              <thead class="table-warning">
                <tr>
                    <th colspan=4></th>
                  <th >ID</th>
                  <th >NUMERO HR</th>
                  <th >CITE EXTERNO</th>
                  <th >FECHA INGRESO</th>
                  <th >ASUNTO</th>
                  <th >CITE INTERNO</th>
                  <th >TIPO PROCESO</th>
                  <th>REMITENTE INTENRNO</th>
                  <th>REMITENTE EXTERNO</th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($tramite as $tramite)
                  <tr>
                    <td width="10px"><a href="{{route('correspondencia.crearDerivacion', $tramite)}}" class="btn btn-outline-primary btn-sm" ><i class="fas fa-arrow-right"></i></a></td>
                    <td width="10px"><button  type="button" class="btn btn-outline-success btn-sm openBtn" data-toggle="modal" data-target="#agregarEmpleado" title="Ver la informacion completa de la persona"><i class="fas fa-fw fa-eye"></i></button></td>
                    <td width="10px"><a href="{{route('correspondencia.crearDerivacion', $tramite)}}" class="btn btn-outline-warning btn-sm" ><i class="fas fa-fw fa-edit"></i></a></td>
                    <td width="10px"><button  type="button" class="btn btn-outline-success btn-sm openBtn" data-toggle="modal" data-target="#agregarEmpleado" title="Ver la informacion completa de la persona"><i class="fas fa-fw fa-eye"></i></button></td>
                    <td width="10px"><a href="{{route('empleado.personal.edit', $tramite)}}" class="btn btn-outline-warning btn-sm" ><i class="fas fa-fw fa-edit"></i></a></td>

                    <td width="10px">
                        <form action="{{route('empleado.personal.destroy', $tramite)}}" method="POST" >
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-fw fa-trash"></i>
                            </button>

                        </form>
                    </td>

                    <td>{{$tramite->id}}</td>
                    <td>{{$tramite->hoja_ruta}}</td>
                    <td>{{$tramite->cite_externo}}</td>
                    <td>{{$tramite->fecha_ingreso}}</td>
                    <td>{{$tramite->asunto}}</td>
                    <td>{{$tramite->cite_interno_id}}</td>
                    <td>{{$tramite->tipo_proceso_id}}</td>
                    <td>{{$tramite->remitente_interno_id}}</td>
                    <td>{{$tramite->remitente_externo_id}}</td>

                    </tr>
                    @endforeach
              </tbody>
            </table>
          </div>
     </div>



    </div>
</div>
@stop


@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop



