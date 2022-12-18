<?php

namespace App\Http\Controllers\Api;

use App\Models\CiteInterno;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CiteInternoResource;
use App\Http\Resources\CiteInternoCollection;
use App\Http\Requests\CiteInternoStoreRequest;
use App\Http\Requests\CiteInternoUpdateRequest;

class CiteInternoController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', CiteInterno::class);

        $search = $request->get('search', '');

        $citeInternos = CiteInterno::search($search)
            ->latest()
            ->paginate();

        return new CiteInternoCollection($citeInternos);
    }

    /**
     * @param \App\Http\Requests\CiteInternoStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CiteInternoStoreRequest $request)
    {
        $this->authorize('create', CiteInterno::class);

        $validated = $request->validated();

        $citeInterno = CiteInterno::create($validated);

        return new CiteInternoResource($citeInterno);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, CiteInterno $citeInterno)
    {
        $this->authorize('view', $citeInterno);

        return new CiteInternoResource($citeInterno);
    }

    /**
     * @param \App\Http\Requests\CiteInternoUpdateRequest $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function update(
        CiteInternoUpdateRequest $request,
        CiteInterno $citeInterno
    ) {
        $this->authorize('update', $citeInterno);

        $validated = $request->validated();

        $citeInterno->update($validated);

        return new CiteInternoResource($citeInterno);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, CiteInterno $citeInterno)
    {
        $this->authorize('delete', $citeInterno);

        $citeInterno->delete();

        return response()->noContent();
    }
}
