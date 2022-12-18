<?php

namespace App\Http\Controllers;

use App\Models\TipoPermiso;
use Illuminate\Http\Request;
use App\Http\Requests\TipoPermisoStoreRequest;
use App\Http\Requests\TipoPermisoUpdateRequest;

class TipoPermisoController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', TipoPermiso::class);

        $search = $request->get('search', '');

        $tipoPermisos = TipoPermiso::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.tipo_permisos.index',
            compact('tipoPermisos', 'search')
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', TipoPermiso::class);

        return view('app.tipo_permisos.create');
    }

    /**
     * @param \App\Http\Requests\TipoPermisoStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TipoPermisoStoreRequest $request)
    {
        $this->authorize('create', TipoPermiso::class);

        $validated = $request->validated();

        $tipoPermiso = TipoPermiso::create($validated);

        return redirect()
            ->route('tipo-permisos.edit', $tipoPermiso)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, TipoPermiso $tipoPermiso)
    {
        $this->authorize('view', $tipoPermiso);

        return view('app.tipo_permisos.show', compact('tipoPermiso'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, TipoPermiso $tipoPermiso)
    {
        $this->authorize('update', $tipoPermiso);

        return view('app.tipo_permisos.edit', compact('tipoPermiso'));
    }

    /**
     * @param \App\Http\Requests\TipoPermisoUpdateRequest $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function update(
        TipoPermisoUpdateRequest $request,
        TipoPermiso $tipoPermiso
    ) {
        $this->authorize('update', $tipoPermiso);

        $validated = $request->validated();

        $tipoPermiso->update($validated);

        return redirect()
            ->route('tipo-permisos.edit', $tipoPermiso)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, TipoPermiso $tipoPermiso)
    {
        $this->authorize('delete', $tipoPermiso);

        $tipoPermiso->delete();

        return redirect()
            ->route('tipo-permisos.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
