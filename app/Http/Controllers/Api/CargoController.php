<?php

namespace App\Http\Controllers\Api;

use App\Models\Cargo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CargoResource;
use App\Http\Resources\CargoCollection;
use App\Http\Requests\CargoStoreRequest;
use App\Http\Requests\CargoUpdateRequest;

class CargoController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Cargo::class);

        $search = $request->get('search', '');

        $cargos = Cargo::search($search)
            ->latest()
            ->paginate();

        return new CargoCollection($cargos);
    }

    /**
     * @param \App\Http\Requests\CargoStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CargoStoreRequest $request)
    {
        $this->authorize('create', Cargo::class);

        $validated = $request->validated();

        $cargo = Cargo::create($validated);

        return new CargoResource($cargo);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Cargo $cargo)
    {
        $this->authorize('view', $cargo);

        return new CargoResource($cargo);
    }

    /**
     * @param \App\Http\Requests\CargoUpdateRequest $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function update(CargoUpdateRequest $request, Cargo $cargo)
    {
        $this->authorize('update', $cargo);

        $validated = $request->validated();

        $cargo->update($validated);

        return new CargoResource($cargo);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Cargo $cargo)
    {
        $this->authorize('delete', $cargo);

        $cargo->delete();

        return response()->noContent();
    }
}
