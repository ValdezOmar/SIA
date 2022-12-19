<?php

namespace App\Http\Controllers\Correspondencia;

use App\Http\Controllers\Controller;
use App\Models\Correspondencia\CorCite;
use Illuminate\Http\Request;
use App\Models\Correspondencia\CorHojaRuta;
use App\Models\Correspondencia\CorRemitenteExterno;
use App\Models\Correspondencia\CorTipoProceso;
use App\Models\User;

class CorBandejaController extends Controller
{
    public function bandejaEntrada(){

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

        return view('correspondencia.bandeja.entrada', compact('hojaruta'));


    }

    public function bandejaPendiente(){

        return view('correspondencia.bandeja.pendientes');

    }

    public function bandejaSalida(){

        return view('correspondencia.bandeja.salida');

    }

    public function archivo(){

        return view('correspondencia.bandeja.archivo');

    }

    public function verTramite(CorHojaRuta $hojaruta){
        $cite = CorCite::pluck('cite', 'id');
        $tipoProceso = CorTipoProceso::pluck('nombre', 'id');
        $remitenteInterno = User::join('personal','users.personal_id', '=', 'personal.id')->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name1, users.id')->pluck('full_name1', 'users.id');
        $remitenteExterno = CorRemitenteExterno::selectRaw('CONCAT(nombres, "  ", apellidos) AS full_name, id')->pluck('full_name', 'id');
        $destinatario = User::join('personal','Users.personal_id', '=', 'personal.id')->pluck('nombres', 'personal.id');
        $remitente = User::join('personal','users.personal_id', '=', 'personal.id')->where('users.id',auth()->user()->id)->selectRaw('CONCAT(personal.nombres, "  ", personal.apellidos) AS full_name, users.id')->pluck('full_name', 'users.id');
        $hojaruta->tramites->all();
        return view('correspondencia.bandeja.verTramite', compact('hojaruta','tipoProceso','remitente','destinatario','cite','tipoProceso','remitenteInterno','remitenteExterno'));
    //return $hojaruta;
    }




}
