<?php

namespace App\Http\Controllers\Api;

use App\Models\Tramite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\DerivacionResource;
use App\Http\Resources\DerivacionCollection;

class TramiteDerivacionesController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        $search = $request->get('search', '');

        $derivaciones = $tramite
            ->derivaciones()
            ->search($search)
            ->latest()
            ->paginate();

        return new DerivacionCollection($derivaciones);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Tramite $tramite
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Tramite $tramite)
    {
        $this->authorize('create', Derivacion::class);

        $validated = $request->validate([
            'remitente_id' => ['nullable', 'exists:users,id'],
            'destinatario_id' => ['nullable', 'max:255'],
            'proveido' => ['required', 'max:255', 'string'],
            'fecha_derivacion' => ['nullable', 'date'],
            'fecha_recepcion' => ['nullable', 'date'],
            'fecha_rechazo' => ['nullable', 'date'],
            'fecha_anulado' => ['nullable', 'date'],
            'fecha_archivo' => ['nullable', 'date'],
        ]);

        $derivacion = $tramite->derivaciones()->create($validated);

        return new DerivacionResource($derivacion);
    }
}
