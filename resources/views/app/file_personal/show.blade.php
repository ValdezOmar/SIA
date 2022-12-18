@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('file-personal.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.file_personal.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.nombres')</h5>
                    <span>{{ $filePersonal->nombres ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.apellidos')</h5>
                    <span>{{ $filePersonal->apellidos ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.fecha_nacimiento')</h5>
                    <span>{{ $filePersonal->fecha_nacimiento ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.CI')</h5>
                    <span>{{ $filePersonal->CI ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.direccion')</h5>
                    <span>{{ $filePersonal->direccion ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.email_personal')</h5>
                    <span>{{ $filePersonal->email_personal ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.telefono_1')</h5>
                    <span>{{ $filePersonal->telefono_1 ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.telefono_2')</h5>
                    <span>{{ $filePersonal->telefono_2 ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.file_personal.inputs.presentacion')</h5>
                    <span>{{ $filePersonal->presentacion ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a
                    href="{{ route('file-personal.index') }}"
                    class="btn btn-light"
                >
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\FilePersonal::class)
                <a
                    href="{{ route('file-personal.create') }}"
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
