<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\TipoDocumento;
use App\Http\Controllers\Controller;
use App\Http\Resources\TramiteResource;
use App\Http\Resources\TramiteCollection;

class TipoDocumentoTramitesController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('view', $tipoDocumento);

        $search = $request->get('search', '');

        $tramites = $tipoDocumento
            ->tramites()
            ->search($search)
            ->latest()
            ->paginate();

        return new TramiteCollection($tramites);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\TipoDocumento $tipoDocumento
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, TipoDocumento $tipoDocumento)
    {
        $this->authorize('create', Tramite::class);

        $validated = $request->validate([
            'hoja_ruta' => ['required', 'max:50', 'string'],
            'recepcion_user_id' => ['required', 'exists:users,id'],
            'fecha_ingreso' => ['required', 'date'],
            'hr_ext' => ['required', 'boolean'],
            'remitente_externo_id' => [
                'required',
                'exists:cor_remitente_externos,id',
            ],
            'cite_ext' => ['nullable', 'max:255', 'string'],
            'asunto_ext' => ['nullable', 'max:255', 'string'],
            'remitente_interno_id' => ['required', 'exists:users,id'],
            'cite_interno_id' => ['nullable', 'exists:cor_cite_internos,id'],
            'asunto_int' => ['nullable', 'max:255'],
            'estado' => ['required', 'max:5', 'string'],
            'fusionado' => ['nullable', 'boolean'],
            'hr_fusionado' => ['nullable', 'max:255', 'string'],
            'hr_fusionado' => ['nullable', 'max:255', 'string'],
        ]);

        $tramite = $tipoDocumento->tramites()->create($validated);

        return new TramiteResource($tramite);
    }
}
