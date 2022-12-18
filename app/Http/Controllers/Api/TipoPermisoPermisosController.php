<?php

namespace App\Http\Controllers\Api;

use App\Models\TipoPermiso;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermisosResource;
use App\Http\Resources\PermisosCollection;

class TipoPermisoPermisosController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, TipoPermiso $tipoPermiso)
    {
        $this->authorize('view', $tipoPermiso);

        $search = $request->get('search', '');

        $permisos = $tipoPermiso
            ->permisos()
            ->search($search)
            ->latest()
            ->paginate();

        return new PermisosCollection($permisos);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoPermiso $tipoPermiso
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, TipoPermiso $tipoPermiso)
    {
        $this->authorize('create', Permisos::class);

        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date'],
            'descripcion' => ['required', 'max:255', 'string'],
            'descripcion_rechazo' => ['nullable', 'max:255', 'string'],
            'permisosable_id' => ['required', 'max:255'],
            'permisosable_type' => ['required', 'max:255', 'string'],
        ]);

        $permisos = $tipoPermiso->permisos()->create($validated);

        return new PermisosResource($permisos);
    }
}
