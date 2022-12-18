<?php

namespace App\Http\Controllers;

use App\Models\CiteInterno;
use Illuminate\Http\Request;
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
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.cite_internos.index',
            compact('citeInternos', 'search')
        );
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', CiteInterno::class);

        return view('app.cite_internos.create');
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

        return redirect()
            ->route('cite-internos.edit', $citeInterno)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, CiteInterno $citeInterno)
    {
        $this->authorize('view', $citeInterno);

        return view('app.cite_internos.show', compact('citeInterno'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, CiteInterno $citeInterno)
    {
        $this->authorize('update', $citeInterno);

        return view('app.cite_internos.edit', compact('citeInterno'));
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

        return redirect()
            ->route('cite-internos.edit', $citeInterno)
            ->withSuccess(__('crud.common.saved'));
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

        return redirect()
            ->route('cite-internos.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
