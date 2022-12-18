@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('areas.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.areas.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.areas.inputs.nombre_area')</h5>
                    <span>{{ $area->nombre_area ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.areas.inputs.prefijo_cite')</h5>
                    <span>{{ $area->prefijo_cite ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.areas.inputs.descripcion_area')</h5>
                    <span>{{ $area->descripcion_area ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('areas.index') }}" class="btn btn-light">
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\Area::class)
                <a href="{{ route('areas.create') }}" class="btn btn-light">
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
