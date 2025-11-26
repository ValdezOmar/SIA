<?php

namespace App\Http\Controllers;

use App\Models\HelpDesk\Equipo;
use Illuminate\Http\Request;

class PublicEquipoController extends Controller
{
    //
    public function show($codigo)
    {
        $equipo = Equipo::with(['cliente', 'empresa', 'tecnico', 'sucursalRelacion'])
            ->where('codigo', $codigo)
            ->firstOrFail();

        return view('filament.pages.equipos-public', compact('equipo'));
    }
}