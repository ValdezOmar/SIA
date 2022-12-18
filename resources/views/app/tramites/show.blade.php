@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('tramites.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.tramites.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.hoja_ruta')</h5>
                    <span>{{ $tramite->hoja_ruta ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.recepcion_user_id')</h5>
                    <span>{{ optional($tramite->user)->name ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.fecha_ingreso')</h5>
                    <span>{{ $tramite->fecha_ingreso ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.hr_ext')</h5>
                    <span>{{ $tramite->hr_ext ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.remitente_externo_id')</h5>
                    <span
                        >{{
                        optional($tramite->remitenteExterno)->nombre_completo ??
                        '-' }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.cite_ext')</h5>
                    <span>{{ $tramite->cite_ext ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.asunto_ext')</h5>
                    <span>{{ $tramite->asunto_ext ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.remitente_interno_id')</h5>
                    <span
                        >{{ optional($tramite->remitente_interno)->name ?? '-'
                        }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.cite_interno_id')</h5>
                    <span
                        >{{ optional($tramite->citeInterno)->cite_interno ?? '-'
                        }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.asunto_int')</h5>
                    <span>{{ $tramite->asunto_int ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.tipo_documento_id')</h5>
                    <span
                        >{{ optional($tramite->tipoDocumento)->tipo_documento ??
                        '-' }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.estado')</h5>
                    <span>{{ $tramite->estado ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.fusionado')</h5>
                    <span>{{ $tramite->fusionado ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.hr_fusionado')</h5>
                    <span>{{ $tramite->hr_fusionado ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.tramites.inputs.hr_fusionado')</h5>
                    <span>{{ $tramite->hr_fusionado ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('tramites.index') }}" class="btn btn-light">
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\Tramite::class)
                <a href="{{ route('tramites.create') }}" class="btn btn-light">
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
