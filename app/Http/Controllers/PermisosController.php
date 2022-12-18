<?php

namespace App\Http\Controllers;

use App\Models\Permisos;
use App\Models\TipoPermiso;
use Illuminate\Http\Request;
use App\Http\Requests\PermisosStoreRequest;
use App\Http\Requests\PermisosUpdateRequest;

class PermisosController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Permisos::class);

        $search = $request->get('search', '');

        $permisos = Permisos::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.permisos.index', compact('permisos', 'search'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', Permisos::class);

        $tipoPermisos = TipoPermiso::pluck('nombre_permiso', 'id');

        return view('app.permisos.create', compact('tipoPermisos'));
    }

    /**
     * @param \App\Http\Requests\PermisosStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(PermisosStoreRequest $request)
    {
        $this->authorize('create', Permisos::class);

        $validated = $request->validated();

        $permisos = Permisos::create($validated);

        return redirect()
            ->route('permisos.edit', $permisos)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Permisos $permisos
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Permisos $permisos)
    {
        $this->authorize('view', $permisos);

        return view('app.permisos.show', compact('permisos'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Permisos $permisos
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Permisos $permisos)
    {
        $this->authorize('update', $permisos);

        $tipoPermisos = TipoPermiso::pluck('nombre_permiso', 'id');

        return view('app.permisos.edit', compact('permisos', 'tipoPermisos'));
    }

    /**
     * @param \App\Http\Requests\PermisosUpdateRequest $request
     * @param \App\Models\Permisos $permisos
     * @return \Illuminate\Http\Response
     */
    public function update(PermisosUpdateRequest $request, Permisos $permisos)
    {
        $this->authorize('update', $permisos);

        $validated = $request->validated();

        $permisos->update($validated);

        return redirect()
            ->route('permisos.edit', $permisos)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Permisos $permisos
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Permisos $permisos)
    {
        $this->authorize('delete', $permisos);

        $permisos->delete();

        return redirect()
            ->route('permisos.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
