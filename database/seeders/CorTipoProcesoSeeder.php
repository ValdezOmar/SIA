<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Correspondencia\CorTipoProceso;

class CorTipoProcesoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $proceso1 = new CorTipoProceso();
        $proceso1->nombre="Compra de activo";
        $proceso1->save();

        $proceso2 = new CorTipoProceso();
        $proceso2->nombre="Pago de servicios";
        $proceso2->save();

        $proceso3 = new CorTipoProceso();
        $proceso3->nombre="Nota externa";
        $proceso3->save();
    }
}
