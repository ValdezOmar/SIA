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
                @can('create', App\Models\Derivacion::class)
                <a
                    href="{{ route('derivaciones.create') }}"
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
                <h4 class="card-title">
                    @lang('crud.derivaciones.index_title')
                </h4>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-hover">
                    <thead>
                        <tr>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.tramite_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.remitente_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.destinatario_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.proveido')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.fecha_derivacion')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.fecha_recepcion')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.fecha_rechazo')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.fecha_anulado')
                            </th>
                            <th class="text-left">
                                @lang('crud.derivaciones.inputs.fecha_archivo')
                            </th>
                            <th class="text-center">
                                @lang('crud.common.actions')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($derivaciones as $derivacion)
                        <tr>
                            <td>
                                {{ optional($derivacion->tramite)->hoja_ruta ??
                                '-' }}
                            </td>
                            <td>
                                {{ optional($derivacion->user)->name ?? '-' }}
                            </td>
                            <td>{{ $derivacion->destinatario_id ?? '-' }}</td>
                            <td>{{ $derivacion->proveido ?? '-' }}</td>
                            <td>{{ $derivacion->fecha_derivacion ?? '-' }}</td>
                            <td>{{ $derivacion->fecha_recepcion ?? '-' }}</td>
                            <td>{{ $derivacion->fecha_rechazo ?? '-' }}</td>
                            <td>{{ $derivacion->fecha_anulado ?? '-' }}</td>
                            <td>{{ $derivacion->fecha_archivo ?? '-' }}</td>
                            <td class="text-center" style="width: 134px;">
                                <div
                                    role="group"
                                    aria-label="Row Actions"
                                    class="btn-group"
                                >
                                    @can('update', $derivacion)
                                    <a
                                        href="{{ route('derivaciones.edit', $derivacion) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-create"></i>
                                        </button>
                                    </a>
                                    @endcan @can('view', $derivacion)
                                    <a
                                        href="{{ route('derivaciones.show', $derivacion) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-eye"></i>
                                        </button>
                                    </a>
                                    @endcan @can('delete', $derivacion)
                                    <form
                                        action="{{ route('derivaciones.destroy', $derivacion) }}"
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
                            <td colspan="10">
                                @lang('crud.common.no_items_found')
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="10">
                                {!! $derivaciones->render() !!}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
