@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('remitente-externos.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.remitente_externo.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>
                        @lang('crud.remitente_externo.inputs.nombre_completo')
                    </h5>
                    <span>{{ $remitenteExterno->nombre_completo ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>
                        @lang('crud.remitente_externo.inputs.cargo_empresa')
                    </h5>
                    <span>{{ $remitenteExterno->cargo_empresa ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.remitente_externo.inputs.telefono_1')</h5>
                    <span>{{ $remitenteExterno->telefono_1 ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.remitente_externo.inputs.telefono_2')</h5>
                    <span>{{ $remitenteExterno->telefono_2 ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a
                    href="{{ route('remitente-externos.index') }}"
                    class="btn btn-light"
                >
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\RemitenteExterno::class)
                <a
                    href="{{ route('remitente-externos.create') }}"
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
