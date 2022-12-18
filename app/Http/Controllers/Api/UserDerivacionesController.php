<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\DerivacionResource;
use App\Http\Resources\DerivacionCollection;

class UserDerivacionesController extends Controller
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

        $derivaciones = $user
            ->derivaciones()
            ->search($search)
            ->latest()
            ->paginate();

        return new DerivacionCollection($derivaciones);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $this->authorize('create', Derivacion::class);

        $validated = $request->validate([
            'tramite_id' => ['required', 'exists:cor_tramites,id'],
            'destinatario_id' => ['nullable', 'max:255'],
            'proveido' => ['required', 'max:255', 'string'],
            'fecha_derivacion' => ['nullable', 'date'],
            'fecha_recepcion' => ['nullable', 'date'],
            'fecha_rechazo' => ['nullable', 'date'],
            'fecha_anulado' => ['nullable', 'date'],
            'fecha_archivo' => ['nullable', 'date'],
        ]);

        $derivacion = $user->derivaciones()->create($validated);

        return new DerivacionResource($derivacion);
    }
}
