<?php

namespace App\Http\Controllers\Api;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CargoResource;
use App\Http\Resources\CargoCollection;

class AreaCargosController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Area $area)
    {
        $this->authorize('view', $area);

        $search = $request->get('search', '');

        $cargos = $area
            ->cargos()
            ->search($search)
            ->latest()
            ->paginate();

        return new CargoCollection($cargos);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Area $area)
    {
        $this->authorize('create', Cargo::class);

        $validated = $request->validate([
            'nombre_cargo' => ['required', 'max:255', 'string'],
            'descripcion_funciones' => ['nullable', 'max:255', 'string'],
        ]);

        $cargo = $area->cargos()->create($validated);

        return new CargoResource($cargo);
    }
}
