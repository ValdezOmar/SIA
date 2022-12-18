<?php

namespace App\Http\Controllers\Api;

use App\Models\Permisos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermisosResource;
use App\Http\Resources\PermisosCollection;
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
            ->paginate();

        return new PermisosCollection($permisos);
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

        return new PermisosResource($permisos);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Permisos $permisos
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Permisos $permisos)
    {
        $this->authorize('view', $permisos);

        return new PermisosResource($permisos);
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

        return new PermisosResource($permisos);
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

        return response()->noContent();
    }
}
