<?php

namespace App\Http\Controllers\Api;

use App\Models\Tramite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TramiteResource;
use App\Http\Resources\TramiteCollection;
use App\Http\Requests\TramiteStoreRequest;
use App\Http\Requests\TramiteUpdateRequest;

class TramiteController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Tramite::class);

        $search = $request->get('search', '');

        $tramites = Tramite::search($search)
            ->latest()
            ->paginate();

        return new TramiteCollection($tramites);
    }

    /**
     * @param \App\Http\Requests\TramiteStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TramiteStoreRequest $request)
    {
        $this->authorize('create', Tramite::class);

        $validated = $request->validated();

        $tramite = Tramite::create($validated);

        return new TramiteResource($tramite);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        return new TramiteResource($tramite);
    }

    /**
     * @param \App\Http\Requests\TramiteUpdateRequest $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function update(TramiteUpdateRequest $request, Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        $validated = $request->validated();

        $tramite->update($validated);

        return new TramiteResource($tramite);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Tramite $tramite)
    {
        $this->authorize('delete', $tramite);

        $tramite->delete();

        return response()->noContent();
    }
}
