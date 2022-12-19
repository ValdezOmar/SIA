<?php

namespace App\Http\Controllers\Correspondencia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Correspondencia\CorTramite;
use App\Models\User;
use App\Models\Correspondencia\CorEstado;
use App\Models\Correspondencia\CorHojaRuta;

class CorTramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tramite = CorTramite::get();
        return view('correspondencia.Tramite.index', compact('tramite'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, CorHojaRuta $hojaruta)
    {
        $hojaruta = CorHojaRuta::find($request);
        $remitente = auth()->user()->id;
        $destinatario = User::join('personal','Users.personal_id', '=', 'personal.id')->pluck('nombres', 'personal.id');
        $estado = CorEstado::pluck('estado', 'id');
        return $hojaruta;

        return view('correspondencia.Tramite.create', compact('remitente', 'destinatario', 'estado', 'hojaruta'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $tramite = CorTramite::create($request->all());
         return redirect()->route('correspondencia.tramite.index', $tramite)->with('info', 'El registro se actualizo con exito');

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
