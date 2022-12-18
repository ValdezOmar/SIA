@extends('layouts.app')

@section('content')
<div class="container">
    <div class="searchbar mt-0 mb-4">
        <div class="row">
            <div class="col-md-6">
                <form>
                    <div class="input-group">
                        <input
                            id="indexSearch"
                            type="text"
                            name="search"
                            placeholder="{{ __('crud.common.search') }}"
                            value="{{ $search ?? '' }}"
                            class="form-control"
                            autocomplete="off"
                        />
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">
                                <i class="icon ion-md-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-right">
                @can('create', App\Models\Permisos::class)
                <a
                    href="{{ route('permisos.create') }}"
                    class="btn btn-primary"
                >
                    <i class="icon ion-md-add"></i> @lang('crud.common.create')
                </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between;">
                <h4 class="card-title">@lang('crud.permisos.index_title')</h4>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-hover">
                    <thead>
                        <tr>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.tipo_permiso_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.fecha_inicio')
                            </th>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.fecha_fin')
                            </th>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.descripcion')
                            </th>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.descripcion_rechazo')
                            </th>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.permisosable_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.permisos.inputs.permisosable_type')
                            </th>
                            <th class="text-center">
                                @lang('crud.common.actions')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permisos as $permisos)
                        <tr>
                            <td>
                                {{
                                optional($permisos->tipoPermiso)->nombre_permiso
                                ?? '-' }}
                            </td>
                            <td>{{ $permisos->fecha_inicio ?? '-' }}</td>
                            <td>{{ $permisos->fecha_fin ?? '-' }}</td>
                            <td>{{ $permisos->descripcion ?? '-' }}</td>
                            <td>{{ $permisos->descripcion_rechazo ?? '-' }}</td>
                            <td>{{ $permisos->permisosable_id ?? '-' }}</td>
                            <td>{{ $permisos->permisosable_type ?? '-' }}</td>
                            <td class="text-center" style="width: 134px;">
                                <div
                                    role="group"
                                    aria-label="Row Actions"
                                    class="btn-group"
                                >
                                    @can('update', $permisos)
                                    <a
                                        href="{{ route('permisos.edit', $permisos) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-create"></i>
                                        </button>
                                    </a>
                                    @endcan @can('view', $permisos)
                                    <a
                                        href="{{ route('permisos.show', $permisos) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-eye"></i>
                                        </button>
                                    </a>
                                    @endcan @can('delete', $permisos)
                                    <form
                                        action="{{ route('permisos.destroy', $permisos) }}"
                                        method="POST"
                                        onsubmit="return confirm('{{ __('crud.common.are_you_sure') }}')"
                                    >
                                        @csrf @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-light text-danger"
                                        >
                                            <i class="icon ion-md-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8">
                                @lang('crud.common.no_items_found')
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8">{!! $permisos->render() !!}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
