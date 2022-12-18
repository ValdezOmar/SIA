<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Resources\TipoDocumentoResource;
use App\Http\Resources\TipoDocumentoCollection;
use App\Http\Requests\TipoDocumentoStoreRequest;
use App\Http\Requests\TipoDocumentoUpdateRequest;

class TipoDocumentoController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', TipoDocumento::class);

        $search = $request->get('search', '');

        $tipoDocumentos = TipoDocumento::search($search)
            ->latest()
            ->paginate();

        return new TipoDocumentoCollection($tipoDocumentos);
    }

    /**
     * @param \App\Http\Requests\TipoDocumentoStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TipoDocumentoStoreRequest $request)
    {
        $this->authorize('create', TipoDocumento::class);

        $validated = $request->validated();

        $tipoDocumento = TipoDocumento::create($validated);

        return new TipoDocumentoResource($tipoDocumento);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('view', $tipoDocumento);

        return new TipoDocumentoResource($tipoDocumento);
    }

    /**
     * @param \App\Http\Requests\TipoDocumentoUpdateRequest $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function update(
        TipoDocumentoUpdateRequest $request,
        TipoDocumento $tipoDocumento
    ) {
        $this->authorize('update', $tipoDocumento);

        $validated = $request->validated();

        $tipoDocumento->update($validated);

        return new TipoDocumentoResource($tipoDocumento);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('delete', $tipoDocumento);

        $tipoDocumento->delete();

        return response()->noContent();
    }
}
