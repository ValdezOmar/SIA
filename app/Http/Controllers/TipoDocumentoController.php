<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoDocumento;
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
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.tipo_documentos.index',
            compact('tipoDocumentos', 'search')
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', TipoDocumento::class);

        return view('app.tipo_documentos.create');
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

        return redirect()
            ->route('tipo-documentos.edit', $tipoDocumento)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('view', $tipoDocumento);

        return view('app.tipo_documentos.show', compact('tipoDocumento'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('update', $tipoDocumento);

        return view('app.tipo_documentos.edit', compact('tipoDocumento'));
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

        return redirect()
            ->route('tipo-documentos.edit', $tipoDocumento)
            ->withSuccess(__('crud.common.saved'));
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

        return redirect()
            ->route('tipo-documentos.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
