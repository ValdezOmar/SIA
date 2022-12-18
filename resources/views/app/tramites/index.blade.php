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
                @can('create', App\Models\Tramite::class)
                <a
                    href="{{ route('tramites.create') }}"
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
                <h4 class="card-title">@lang('crud.tramites.index_title')</h4>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-hover">
                    <thead>
                        <tr>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.hoja_ruta')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.recepcion_user_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.fecha_ingreso')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.hr_ext')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.remitente_externo_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.cite_ext')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.asunto_ext')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.remitente_interno_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.cite_interno_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.asunto_int')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.tipo_documento_id')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.estado')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.fusionado')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.hr_fusionado')
                            </th>
                            <th class="text-left">
                                @lang('crud.tramites.inputs.hr_fusionado')
                            </th>
                            <th class="text-center">
                                @lang('crud.common.actions')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tramites as $tramite)
                        <tr>
                            <td>{{ $tramite->hoja_ruta ?? '-' }}</td>
                            <td>{{ optional($tramite->user)->name ?? '-' }}</td>
                            <td>{{ $tramite->fecha_ingreso ?? '-' }}</td>
                            <td>{{ $tramite->hr_ext ?? '-' }}</td>
                            <td>
                                {{
                                optional($tramite->remitenteExterno)->nombre_completo
                                ?? '-' }}
                            </td>
                            <td>{{ $tramite->cite_ext ?? '-' }}</td>
                            <td>{{ $tramite->asunto_ext ?? '-' }}</td>
                            <td>
                                {{ optional($tramite->remitente_interno)->name
                                ?? '-' }}
                            </td>
                            <td>
                                {{ optional($tramite->citeInterno)->cite_interno
                                ?? '-' }}
                            </td>
                            <td>{{ $tramite->asunto_int ?? '-' }}</td>
                            <td>
                                {{
                                optional($tramite->tipoDocumento)->tipo_documento
                                ?? '-' }}
                            </td>
                            <td>{{ $tramite->estado ?? '-' }}</td>
                            <td>{{ $tramite->fusionado ?? '-' }}</td>
                            <td>{{ $tramite->hr_fusionado ?? '-' }}</td>
                            <td>{{ $tramite->hr_fusionado ?? '-' }}</td>
                            <td class="text-center" style="width: 134px;">
                                <div
                                    role="group"
                                    aria-label="Row Actions"
                                    class="btn-group"
                                >
                                    @can('update', $tramite)
                                    <a
                                        href="{{ route('tramites.edit', $tramite) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-create"></i>
                                        </button>
                                    </a>
                                    @endcan @can('view', $tramite)
                                    <a
                                        href="{{ route('tramites.show', $tramite) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-eye"></i>
                                        </button>
                                    </a>
                                    @endcan @can('delete', $tramite)
                                    <form
                                        action="{{ route('tramites.destroy', $tramite) }}"
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
                            <td colspan="16">
                                @lang('crud.common.no_items_found')
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="16">{!! $tramites->render() !!}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
