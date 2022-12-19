<?php

namespace App\Http\Controllers\Empleado;

use App\Http\Controllers\Controller;
use App\Models\Empleado\Cargo;
use App\Models\Empleado\Personal;
use Illuminate\Http\Request;
// use App\Models\Empleado\Empleado;
use App\Models\User;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $balanceTransfer = DB::table('user_balance_transfer as bt')->where('bt.transfer_by', '=', $username)
        // ->join('users as u', 'bt.transfer_to', '=', 'u.username')
        // ->join('user_balances as ub', function($join)
        //  {
        //      $join->on('bt.transaction_id', '=', 'ub.transaction_id')->where('ub.type', '=', 'transfer');
        //  })
        // ->select('bt.*', 'u.first_name', 'u.last_name', 'u.profile_photo', 'ub.current_balance')
        // ->orderBy('bt.id', 'desc')->get();



        // $empleado = Empleado::join("cargos","empleados.cargo_id","=","cargos.id")->get();

        $empleado = User::join('cargos','users.cargo_id', '=', 'cargos.id')
                    ->join('unidades','cargos.unidad_id', '=', 'unidades.id')
                    ->join('personal','users.personal_id', '=', 'personal.id')
                    //->join('users','empleados.user_id', '=', 'users.id')
                     ->get();

        return view('empleado.empleado.index', compact('empleado'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // $personal = Personal::get()->pluck("nombres,apellidos",'id');
        //$personal = Personal::selectRaw("CONCAT ('nombres', 'apellidos') as nombreCompleto, id")->pluck('nombreCompleto','id');
        $personal = Personal::selectRaw('CONCAT(nombres, "  ", apellidos) AS full_name, id')->pluck('full_name', 'id');
        $cargo = Cargo::pluck('cargo','id');

        return view('empleado.empleado.create', compact('cargo', 'personal'));
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
         $empleado = User::create($request->all());
         return redirect()->route('empleado.empleado.index')->with('info', 'El registro se actualizo con exito');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $empleado)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $empleado)
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
    public function update(Request $request, User $empleado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $empleado)
    {
        //
    }
}
