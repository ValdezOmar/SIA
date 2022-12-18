@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('derivaciones.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.derivaciones.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.tramite_id')</h5>
                    <span
                        >{{ optional($derivacion->tramite)->hoja_ruta ?? '-'
                        }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.remitente_id')</h5>
                    <span>{{ optional($derivacion->user)->name ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.destinatario_id')</h5>
                    <span>{{ $derivacion->destinatario_id ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.proveido')</h5>
                    <span>{{ $derivacion->proveido ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.fecha_derivacion')</h5>
                    <span>{{ $derivacion->fecha_derivacion ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.fecha_recepcion')</h5>
                    <span>{{ $derivacion->fecha_recepcion ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.fecha_rechazo')</h5>
                    <span>{{ $derivacion->fecha_rechazo ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.fecha_anulado')</h5>
                    <span>{{ $derivacion->fecha_anulado ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.derivaciones.inputs.fecha_archivo')</h5>
                    <span>{{ $derivacion->fecha_archivo ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a
                    href="{{ route('derivaciones.index') }}"
                    class="btn btn-light"
                >
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\Derivacion::class)
                <a
                    href="{{ route('derivaciones.create') }}"
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
