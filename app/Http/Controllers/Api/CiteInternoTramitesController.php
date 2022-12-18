<?php

namespace App\Http\Controllers\Api;

use App\Models\CiteInterno;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TramiteResource;
use App\Http\Resources\TramiteCollection;

class CiteInternoTramitesController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, CiteInterno $citeInterno)
    {
        $this->authorize('view', $citeInterno);

        $search = $request->get('search', '');

        $tramites = $citeInterno
            ->tramites()
            ->search($search)
            ->latest()
            ->paginate();

        return new TramiteCollection($tramites);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CiteInterno $citeInterno
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, CiteInterno $citeInterno)
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
            'asunto_int' => ['nullable', 'max:255'],
            'tipo_documento_id' => [
                'nullable',
                'exists:cor_tipo_documentos,id',
            ],
            'estado' => ['required', 'max:5', 'string'],
            'fusionado' => ['nullable', 'boolean'],
            'hr_fusionado' => ['nullable', 'max:255', 'string'],
            'hr_fusionado' => ['nullable', 'max:255', 'string'],
        ]);

        $tramite = $citeInterno->tramites()->create($validated);

        return new TramiteResource($tramite);
    }
}
