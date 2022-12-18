<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RemitenteExterno;
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
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.remitente_externos.index',
            compact('remitenteExternos', 'search')
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', RemitenteExterno::class);

        return view('app.remitente_externos.create');
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

        return redirect()
            ->route('remitente-externos.edit', $remitenteExterno)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RemitenteExterno $remitenteExterno
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, RemitenteExterno $remitenteExterno)
    {
        $this->authorize('view', $remitenteExterno);

        return view('app.remitente_externos.show', compact('remitenteExterno'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RemitenteExterno $remitenteExterno
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, RemitenteExterno $remitenteExterno)
    {
        $this->authorize('update', $remitenteExterno);

        return view('app.remitente_externos.edit', compact('remitenteExterno'));
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

        return redirect()
            ->route('remitente-externos.edit', $remitenteExterno)
            ->withSuccess(__('crud.common.saved'));
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

        return redirect()
            ->route('remitente-externos.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
