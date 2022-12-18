<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TramiteResource;
use App\Http\Resources\TramiteCollection;

class UserTramitesController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, User $user)
    {
        $this->authorize('view', $user);

        $search = $request->get('search', '');

        $tramites = $user
            ->tramites2()
            ->search($search)
            ->latest()
            ->paginate();

        return new TramiteCollection($tramites);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $this->authorize('create', Tramite::class);

        $validated = $request->validate([
            'hoja_ruta' => ['required', 'max:50', 'string'],
            'fecha_ingreso' => ['required', 'date'],
            'hr_ext' => ['required', 'boolean'],
            'remitente_externo_id' => [
                'required',
                'exists:cor_remitente_externos,id',
            ],
            'cite_ext' => ['nullable', 'max:255', 'string'],
            'asunto_ext' => ['nullable', 'max:255', 'string'],
            'cite_interno_id' => ['nullable', 'exists:cor_cite_internos,id'],
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

        $tramite = $user->tramites2()->create($validated);

        return new TramiteResource($tramite);
    }
}
