<?php

namespace App\Http\Controllers\Api;

use App\Models\TipoPermiso;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TipoPermisoResource;
use App\Http\Resources\TipoPermisoCollection;
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
            ->paginate();

        return new TipoPermisoCollection($tipoPermisos);
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

        return new TipoPermisoResource($tipoPermiso);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, TipoPermiso $tipoPermiso)
    {
        $this->authorize('view', $tipoPermiso);

        return new TipoPermisoResource($tipoPermiso);
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

        return new TipoPermisoResource($tipoPermiso);
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

        return response()->noContent();
    }
}
