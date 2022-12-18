<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Cargo;
use Illuminate\Http\Request;
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
            ->paginate(5)
            ->withQueryString();

        return view('app.cargos.index', compact('cargos', 'search'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', Cargo::class);

        $areas = Area::pluck('nombre_area', 'id');

        return view('app.cargos.create', compact('areas'));
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

        return redirect()
            ->route('cargos.edit', $cargo)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Cargo $cargo)
    {
        $this->authorize('view', $cargo);

        return view('app.cargos.show', compact('cargo'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Cargo $cargo)
    {
        $this->authorize('update', $cargo);

        $areas = Area::pluck('nombre_area', 'id');

        return view('app.cargos.edit', compact('cargo', 'areas'));
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

        return redirect()
            ->route('cargos.edit', $cargo)
            ->withSuccess(__('crud.common.saved'));
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

        return redirect()
            ->route('cargos.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
