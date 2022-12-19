<?php

namespace App\Http\Controllers\Correspondencia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Correspondencia\CorHojaRuta;
use App\Models\Correspondencia\CorCite;
use App\Models\Correspondencia\CorRemitenteExterno;
use App\Models\Correspondencia\CorTipoProceso;
use App\Models\User;
use App\Models\Correspondencia\CorTramite;

class CorHojaRutaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $hojaruta = CorHojaRuta::leftjoin('cor_cites','cor_hoja_rutas.cite_interno_id', '=', 'cor_cites.id')
                    ->join('cor_tipo_procesos','cor_hoja_rutas.tipo_proceso_id', '=', 'cor_tipo_procesos.id')
                    ->leftjoin('users','cor_hoja_rutas.remitente_interno_id', '=', 'users.id')->leftjoin('personal','users.personal_id', '=', 'personal.id')
                    ->leftjoin('cor_remitente_externos','cor_hoja_rutas.remitente_externo_id', '=', 'cor_remitente_externos.id')
                    ->select('cor_hoja_rutas.id','cor_hoja_rutas.hoja_ruta', 'cor_hoja_rutas.cite_externo','cor_hoja_rutas.hr_externo', 'cor_hoja_rutas.fecha_ingreso', 'cor_hoja_rutas.asunto',
                                'cor_cites.cite as cite_interno', 'cor_tipo_procesos.nombre as tipo_proceso',
                                'cor_remitente_externos.nombres as nombres_externos', 'cor_remitente_externos.empresa',
                                'personal.nombres', 'personal.apellidos')->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name_interno')
                                ->selectRaw('CONCAT(cor_remitente_externos.nombres, "  ", cor_remitente_externos.apellidos) AS full_name_externo')
                                ->get();

        return view('correspondencia.hojaruta.index', compact('hojaruta'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $cite = CorCite::pluck('cite', 'id');
        $tipoProceso = CorTipoProceso::pluck('nombre', 'id');
        $remitenteInterno = User::join('personal','users.personal_id', '=', 'personal.id')->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name1, users.id')->pluck('full_name1', 'users.id');
        $remitenteExterno = CorRemitenteExterno::selectRaw('CONCAT(nombres, "  ", apellidos) AS full_name, id')->pluck('full_name', 'id');
        $destinatario = User::join('personal','Users.personal_id', '=', 'personal.id')->pluck('nombres', 'personal.id');
        $remitente = User::join('personal','users.personal_id', '=', 'personal.id')->where('users.id',auth()->user()->id)->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name, users.id')->pluck('full_name', 'users.id');

        // $remitente = auth()->user()->id;
        //return $remitenteInterno;
        return view('correspondencia.hojaruta.create', compact('tipoProceso', 'cite','remitenteInterno', 'remitenteExterno', 'destinatario', 'remitente'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'hoja_ruta' => 'required',
            'fecha_ingreso' => 'required',
            'asunto' => 'required',
            'tipo_proceso_id' => 'required'
        ]);
        $tramite = CorTramite::create([
            'remitente' => $request->remitente,
            'destinatario' => $request->destinatario,
            'proveido' => 'Primera derivacion desde Ventanilla Unica',
            'fecha_derivacion' => now(),
            'estado' => '1'
        ]);

        $hojaruta = CorHojaRuta::create($request->all());
         //return $hojaruta;

        //return $tramite;

        $hojaruta->tramites()->attach($tramite->id);
        return $hojaruta;

         //return redirect()->route('correspondencia.hojaruta.index', $hojaruta)->with('info', 'El registro se actualizo con exito');

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
    public function edit(CorHojaRuta $hojaruta)
    {
        $cite = CorCite::pluck('cite', 'id');
        $tipoProceso = CorTipoProceso::pluck('nombre', 'id');
        $remitenteInterno = User::join('personal','users.personal_id', '=', 'personal.id')->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name1, users.id')->pluck('full_name1', 'users.id');
        $remitenteExterno = CorRemitenteExterno::selectRaw('CONCAT(nombres, "  ", apellidos) AS full_name, id')->pluck('full_name', 'id');

        //return $hoja_ruta;
        return view('correspondencia.hojaruta.edit', compact ('hojaruta','remitenteExterno','cite','tipoProceso', 'remitenteInterno'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CorHojaRuta $hoja_ruta)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CorHojaRuta $hoja_ruta)
    {
        //
    }

    public function crearDerivacion(CorHojaRuta $hoja_ruta)
    {
        $empleadoLogueado = auth()->user()->id;
        //$empleadoLogueado = Empleado::where('user_id', $user)->get();
        // CorTramite::create(['remitente' => 'Flight 10']);


        return compact('empleadoLogueado');
    }

    public function derivar(CorHojaRuta $hoja_ruta)
    {

        $tramite = CorTramite::all();

    }
}
