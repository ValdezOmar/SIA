<?php

namespace Database\Seeders;

use App\Models\Correspondencia\CorEstado;
use Illuminate\Database\Seeder;

class CorEstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $estado1 = new CorEstado();
        $estado1->estado="RECEPCION";
        $estado1->save();

        $estado1 = new CorEstado();
        $estado1->estado="DERIVADO";
        $estado1->save();

        $estado2 = new CorEstado();
        $estado2->estado="PENDIENTE";
        $estado2->save();

        $estado3 = new CorEstado();
        $estado3->estado="RECHAZADO";
        $estado3->save();

        $estado4 = new CorEstado();
        $estado4->estado="PROCESO RECHAZADO";
        $estado4->save();

        $estado6 = new CorEstado();
        $estado6->estado="ACEPTADO";
        $estado6->save();

        $estado6 = new CorEstado();
        $estado6->estado="ARCHIVO";
        $estado6->save();

        $estado7 = new CorEstado();
        $estado7->estado="ANULADO";
        $estado7->save();

    }
}
