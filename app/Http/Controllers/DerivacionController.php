<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tramite;
use App\Models\Derivacion;
use Illuminate\Http\Request;
use App\Http\Requests\DerivacionStoreRequest;
use App\Http\Requests\DerivacionUpdateRequest;

class DerivacionController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Derivacion::class);

        $search = $request->get('search', '');

        $derivaciones = Derivacion::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.derivaciones.index',
            compact('derivaciones', 'search')
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', Derivacion::class);

        $tramites = Tramite::pluck('hoja_ruta', 'id');
        $users = User::pluck('name', 'id');

        return view('app.derivaciones.create', compact('tramites', 'users'));
    }

    /**
     * @param \App\Http\Requests\DerivacionStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(DerivacionStoreRequest $request)
    {
        $this->authorize('create', Derivacion::class);

        $validated = $request->validated();

        $derivacion = Derivacion::create($validated);

        return redirect()
            ->route('derivaciones.edit', $derivacion)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Derivacion $derivacion
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Derivacion $derivacion)
    {
        $this->authorize('view', $derivacion);

        return view('app.derivaciones.show', compact('derivacion'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Derivacion $derivacion
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Derivacion $derivacion)
    {
        $this->authorize('update', $derivacion);

        $tramites = Tramite::pluck('hoja_ruta', 'id');
        $users = User::pluck('name', 'id');

        return view(
            'app.derivaciones.edit',
            compact('derivacion', 'tramites', 'users')
        );
    }

    /**
     * @param \App\Http\Requests\DerivacionUpdateRequest $request
     * @param \App\Models\Derivacion $derivacion
     * @return \Illuminate\Http\Response
     */
    public function update(
        DerivacionUpdateRequest $request,
        Derivacion $derivacion
    ) {
        $this->authorize('update', $derivacion);

        $validated = $request->validated();

        $derivacion->update($validated);

        return redirect()
            ->route('derivaciones.edit', $derivacion)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Derivacion $derivacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Derivacion $derivacion)
    {
        $this->authorize('delete', $derivacion);

        $derivacion->delete();

        return redirect()
            ->route('derivaciones.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
