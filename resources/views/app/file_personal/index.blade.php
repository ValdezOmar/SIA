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
                @can('create', App\Models\FilePersonal::class)
                <a
                    href="{{ route('file-personal.create') }}"
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
                    @lang('crud.file_personal.index_title')
                </h4>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless table-hover">
                    <thead>
                        <tr>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.nombres')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.apellidos')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.fecha_nacimiento')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.CI')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.direccion')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.email_personal')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.telefono_1')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.telefono_2')
                            </th>
                            <th class="text-left">
                                @lang('crud.file_personal.inputs.presentacion')
                            </th>
                            <th class="text-center">
                                @lang('crud.common.actions')
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($filePersonal as $filePersonal)
                        <tr>
                            <td>{{ $filePersonal->nombres ?? '-' }}</td>
                            <td>{{ $filePersonal->apellidos ?? '-' }}</td>
                            <td>
                                {{ $filePersonal->fecha_nacimiento ?? '-' }}
                            </td>
                            <td>{{ $filePersonal->CI ?? '-' }}</td>
                            <td>{{ $filePersonal->direccion ?? '-' }}</td>
                            <td>{{ $filePersonal->email_personal ?? '-' }}</td>
                            <td>{{ $filePersonal->telefono_1 ?? '-' }}</td>
                            <td>{{ $filePersonal->telefono_2 ?? '-' }}</td>
                            <td>{{ $filePersonal->presentacion ?? '-' }}</td>
                            <td class="text-center" style="width: 134px;">
                                <div
                                    role="group"
                                    aria-label="Row Actions"
                                    class="btn-group"
                                >
                                    @can('update', $filePersonal)
                                    <a
                                        href="{{ route('file-personal.edit', $filePersonal) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-create"></i>
                                        </button>
                                    </a>
                                    @endcan @can('view', $filePersonal)
                                    <a
                                        href="{{ route('file-personal.show', $filePersonal) }}"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-light"
                                        >
                                            <i class="icon ion-md-eye"></i>
                                        </button>
                                    </a>
                                    @endcan @can('delete', $filePersonal)
                                    <form
                                        action="{{ route('file-personal.destroy', $filePersonal) }}"
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
                                <!-- {!! $filePersonal->render() !!} -->
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
