<?php

namespace App\Http\Controllers\Api;

use App\Models\Cargo;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserCollection;

class CargoUsersController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Cargo $cargo)
    {
        $this->authorize('view', $cargo);

        $search = $request->get('search', '');

        $users = $cargo
            ->users()
            ->search($search)
            ->latest()
            ->paginate();

        return new UserCollection($users);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Cargo $cargo
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Cargo $cargo)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'file_personal_id' => ['required', 'exists:hr_file_personal,id'],
            'activo' => ['required', 'boolean'],
            'telefono_int' => ['nullable', 'max:255', 'string'],
            'name' => ['nullable', 'max:255', 'string'],
            'email' => ['required', 'unique:users,email', 'email'],
            'password' => ['required'],
            'fecha_alta' => ['required', 'date'],
            'fecha_baja' => ['nullable', 'date'],
            'fecha_cambio' => ['nullable', 'date'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = $cargo->users()->create($validated);

        return new UserResource($user);
    }
}
