<?php

namespace App\Http\Controllers\Api;

use App\Models\FilePersonal;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserCollection;

class FilePersonalUsersController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, FilePersonal $filePersonal)
    {
        $this->authorize('view', $filePersonal);

        $search = $request->get('search', '');

        $users = $filePersonal
            ->users()
            ->search($search)
            ->latest()
            ->paginate();

        return new UserCollection($users);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FilePersonal $filePersonal
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, FilePersonal $filePersonal)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'activo' => ['required', 'boolean'],
            'cargo_id' => ['required', 'exists:hr_cargos,id'],
            'telefono_int' => ['nullable', 'max:255', 'string'],
            'name' => ['nullable', 'max:255', 'string'],
            'email' => ['required', 'unique:users,email', 'email'],
            'password' => ['required'],
            'fecha_alta' => ['required', 'date'],
            'fecha_baja' => ['nullable', 'date'],
            'fecha_cambio' => ['nullable', 'date'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = $filePersonal->users()->create($validated);

        return new UserResource($user);
    }
}
