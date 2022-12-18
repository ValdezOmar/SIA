@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">
                <a href="{{ route('users.index') }}" class="mr-4"
                    ><i class="icon ion-md-arrow-back"></i
                ></a>
                @lang('crud.empleados.show_title')
            </h4>

            <div class="mt-4">
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.file_personal_id')</h5>
                    <span
                        >{{ optional($user->filePersonal)->nombres ?? '-'
                        }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.activo')</h5>
                    <span>{{ $user->activo ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.cargo_id')</h5>
                    <span
                        >{{ optional($user->cargo)->nombre_cargo ?? '-' }}</span
                    >
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.telefono_int')</h5>
                    <span>{{ $user->telefono_int ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.name')</h5>
                    <span>{{ $user->name ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.email')</h5>
                    <span>{{ $user->email ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.fecha_alta')</h5>
                    <span>{{ $user->fecha_alta ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.fecha_baja')</h5>
                    <span>{{ $user->fecha_baja ?? '-' }}</span>
                </div>
                <div class="mb-4">
                    <h5>@lang('crud.empleados.inputs.fecha_cambio')</h5>
                    <span>{{ $user->fecha_cambio ?? '-' }}</span>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('users.index') }}" class="btn btn-light">
                    <i class="icon ion-md-return-left"></i>
                    @lang('crud.common.back')
                </a>

                @can('create', App\Models\User::class)
                <a href="{{ route('users.create') }}" class="btn btn-light">
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
