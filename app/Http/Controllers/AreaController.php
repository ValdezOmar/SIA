<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use App\Http\Requests\AreaStoreRequest;
use App\Http\Requests\AreaUpdateRequest;
use App\Models\VariablesEmpresa;

class AreaController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('view-any', Area::class);

        $search = $request->get('search', '');

        $areas = Area::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.areas.index', compact('areas', 'search'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->authorize('create', Area::class);

        return view('app.areas.create');
    }

    /**
     * @param \App\Http\Requests\AreaStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(AreaStoreRequest $request)
    {
        $this->authorize('create', Area::class);

        $validated = $request->validated();

        $area = Area::create($validated);
        $prefijoCite = $area->nombre_area;
        $variablesEmpresa = VariablesEmpresa::all();
        if(VariablesEmpresa::all()->last()->type_id){
            VariablesEmpresa::create([              
                'type_id' => 1 + VariablesEmpresa::all()->last()->type_id,
                'type' => strtoupper(substr($prefijoCite, 0, 3)),
                'value' => 1,
                'status' => true
            ]);
            
        } else {
            VariablesEmpresa::create([              
                'type_id' => 2,
                'type' => $prefijoCite,
                'value' => 1,
                'status' => true
            ]);
        } 
        

        return redirect()
            ->route('areas.edit', $area)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Area $area)
    {
        $this->authorize('view', $area);

        return view('app.areas.show', compact('area'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Area $area)
    {
        $this->authorize('update', $area);

        return view('app.areas.edit', compact('area'));
    }

    /**
     * @param \App\Http\Requests\AreaUpdateRequest $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function update(AreaUpdateRequest $request, Area $area)
    {
        $this->authorize('update', $area);

        $validated = $request->validated();

        $area->update($validated);

        return redirect()
            ->route('areas.edit', $area)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Area $area
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Area $area)
    {
        $this->authorize('delete', $area);

        $area->delete();

        return redirect()
            ->route('areas.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
