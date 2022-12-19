<?php

namespace App\Http\Controllers\Correspondencia;

use App\Http\Controllers\Controller;
use App\Models\Correspondencia\CorCite;
use App\Models\Empleado\Cargo;
use Illuminate\Http\Request;
use App\Models\User;

class CorCiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $cite = CorCite::join('cargos','cor_cites.cargo_id','cargos.id')
                ->join('users','cor_cites.elaborador_id','users.id')->join('personal','users.personal_id','personal.id')
                ->select('cor_cites.id', 'cor_cites.cite', 'cor_cites.asunto', 'cor_cites.created_at', 'cor_cites.asunto', 'cargos.cargo')
                ->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name')->get();

        // return $cite;
        return view('correspondencia.cite.index', compact('cite'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $usuario = auth()->user()->id;
        $cargoUsuario = auth()->user()->cargo_id;
        $elaborador = User::join('personal','users.personal_id', '=', 'personal.id')->where('users.id',$usuario)->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name, users.id')->pluck('full_name', 'users.id');
        $cargo = User::join('cargos','users.personal_id', '=', 'cargos.id')->where('users.cargo_id',$cargoUsuario)->pluck('cargo', 'users.cargo_id');

        return view('correspondencia.cite.create', compact('elaborador', 'cargo'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $request->validate([
        //     'hoja_ruta' => 'required',
        //     'fecha_ingreso' => 'required',
        //     'asunto' => 'required',
        //     'tipo_proceso_id' => 'required'
        // ]);
        //return $request->all();
         $cite = CorCite::create($request->all());
         return redirect()->route('correspondencia.cite.index')->with('info', 'El registro se creo con exito');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
