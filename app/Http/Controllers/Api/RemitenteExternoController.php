<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\RemitenteExterno;
use App\Http\Controllers\Controller;
use App\Http\Resources\RemitenteExternoResource;
use App\Http\Resources\RemitenteExternoCollection;
use App\Http\Requests\RemitenteExternoStoreRequest;
use App\Http\Requests\RemitenteExternoUpdateRequest;

class RemitenteExternoController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', RemitenteExterno::class);

        $search = $request->get('search', '');

        $remitenteExternos = RemitenteExterno::search($search)
            ->latest()
            ->paginate();

        return new RemitenteExternoCollection($remitenteExternos);
    }

    /**
     * @param \App\Http\Requests\RemitenteExternoStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(RemitenteExternoStoreRequest $request)
    {
        $this->authorize('create', RemitenteExterno::class);

        $validated = $request->validated();

        $remitenteExterno = RemitenteExterno::create($validated);

        return new RemitenteExternoResource($remitenteExterno);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RemitenteExterno $remitenteExterno
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, RemitenteExterno $remitenteExterno)
    {
        $this->authorize('view', $remitenteExterno);

        return new RemitenteExternoResource($remitenteExterno);
    }

    /**
     * @param \App\Http\Requests\RemitenteExternoUpdateRequest $request
     * @param \App\Models\RemitenteExterno $remitenteExterno
     * @return \Illuminate\Http\Response
     */
    public function update(
        RemitenteExternoUpdateRequest $request,
        RemitenteExterno $remitenteExterno
    ) {
        $this->authorize('update', $remitenteExterno);

        $validated = $request->validated();

        $remitenteExterno->update($validated);

        return new RemitenteExternoResource($remitenteExterno);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RemitenteExterno $remitenteExterno
     * @return \Illuminate\Http\Response
     */
    public function destroy(
        Request $request,
        RemitenteExterno $remitenteExterno
    ) {
        $this->authorize('delete', $remitenteExterno);

        $remitenteExterno->delete();

        return response()->noContent();
    }
}
