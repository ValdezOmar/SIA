<?php

namespace App\Http\Controllers\Api;

use App\Models\Derivacion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\DerivacionResource;
use App\Http\Resources\DerivacionCollection;
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
            ->paginate();

        return new DerivacionCollection($derivaciones);
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

        return new DerivacionResource($derivacion);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Derivacion $derivacion
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Derivacion $derivacion)
    {
        $this->authorize('view', $derivacion);

        return new DerivacionResource($derivacion);
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

        return new DerivacionResource($derivacion);
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

        return response()->noContent();
    }
}
