<?php

namespace App\Http\Controllers;

use App\Models\RRHH\Empleado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmpleadoFotoController extends Controller
{
    public function __invoke(Empleado $empleado): BinaryFileResponse|RedirectResponse
    {
        $path = $empleado->foto_storage_path;

        if (! $path) {
            return redirect(asset('images/default-avatar.jpg'));
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'private, max-age=86400',
            'Content-Disposition' => 'inline',
        ]);
    }
}
