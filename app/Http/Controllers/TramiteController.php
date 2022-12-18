<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tramite;
use App\Models\CiteInterno;
use Illuminate\Http\Request;
use App\Models\TipoDocumento;
use App\Models\RemitenteExterno;
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
            ->paginate(5)
            ->withQueryString();

        return view('app.tramites.index', compact('tramites', 'search'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', Tramite::class);

        $users = User::pluck('name', 'id');
        $remitenteExternos = RemitenteExterno::pluck('nombre_completo', 'id');
        $citeInternos = CiteInterno::pluck('cite_interno', 'id');
        $tipoDocumentos = TipoDocumento::pluck('tipo_documento', 'id');

        return view(
            'app.tramites.create',
            compact(
                'users',
                'remitenteExternos',
                'users',
                'citeInternos',
                'tipoDocumentos'
            )
        );
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

        return redirect()
            ->route('tramites.edit', $tramite)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        return view('app.tramites.show', compact('tramite'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        $users = User::pluck('name', 'id');
        $remitenteExternos = RemitenteExterno::pluck('nombre_completo', 'id');
        $citeInternos = CiteInterno::pluck('cite_interno', 'id');
        $tipoDocumentos = TipoDocumento::pluck('tipo_documento', 'id');

        return view(
            'app.tramites.edit',
            compact(
                'tramite',
                'users',
                'remitenteExternos',
                'users',
                'citeInternos',
                'tipoDocumentos'
            )
        );
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

        return redirect()
            ->route('tramites.edit', $tramite)
            ->withSuccess(__('crud.common.saved'));
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

        return redirect()
            ->route('tramites.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
