@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('cargos.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.cargos.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.cargos.inputs.nombre_cargo')</h5>
                    <span>{{ $cargo->nombre_cargo ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.cargos.inputs.area_id')</h5>
                    <span
                        >{{ optional($cargo->area)->nombre_area ?? '-' }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.cargos.inputs.descripcion_funciones')</h5>
                    <span>{{ $cargo->descripcion_funciones ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('cargos.index') }}" class="btn btn-light">
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\Cargo::class)
                <a href="{{ route('cargos.create') }}" class="btn btn-light">
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
