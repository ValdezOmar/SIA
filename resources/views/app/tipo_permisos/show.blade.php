@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('tipo-permisos.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.tipo_permisos.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.tipo_permisos.inputs.nombre_permiso')</h5>
                    <span>{{ $tipoPermiso->nombre_permiso ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tipo_permisos.inputs.tipo_permiso')</h5>
                    <span>{{ $tipoPermiso->tipo_permiso ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tipo_permisos.inputs.tiempo_permitido')</h5>
                    <span>{{ $tipoPermiso->tiempo_permitido ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>
                        @lang('crud.tipo_permisos.inputs.descripcion_permiso')
                    </h5>
                    <span>{{ $tipoPermiso->descripcion_permiso ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a
                    href="{{ route('tipo-permisos.index') }}"
                    class="btn btn-light"
                >
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\TipoPermiso::class)
                <a
                    href="{{ route('tipo-permisos.create') }}"
                    class="btn btn-light"
                >
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
