@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('permisos.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.permisos.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.tipo_permiso_id')</h5>
                    <span
                        >{{ optional($permisos->tipoPermiso)->nombre_permiso ??
                        '-' }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.fecha_inicio')</h5>
                    <span>{{ $permisos->fecha_inicio ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.fecha_fin')</h5>
                    <span>{{ $permisos->fecha_fin ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.descripcion')</h5>
                    <span>{{ $permisos->descripcion ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.descripcion_rechazo')</h5>
                    <span>{{ $permisos->descripcion_rechazo ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.permisosable_id')</h5>
                    <span>{{ $permisos->permisosable_id ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.permisos.inputs.permisosable_type')</h5>
                    <span>{{ $permisos->permisosable_type ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('permisos.index') }}" class="btn btn-light">
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\Permisos::class)
                <a href="{{ route('permisos.create') }}" class="btn btn-light">
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
