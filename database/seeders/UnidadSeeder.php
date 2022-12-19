<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empleado\Unidad;

class UnidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $unidad1 = new Unidad();
        $unidad1->unidad = "Gerencia";
        $unidad1->save();

        $unidad2 = new Unidad();
        $unidad2 -> unidad = "Administracion";
        $unidad2->save();


    }
}
