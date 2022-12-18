<?php

namespace Database\Seeders;

use App\Models\TipoPermiso;
use Illuminate\Database\Seeder;

class TipoPermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TipoPermiso::factory()
            ->count(5)
            ->create();
    }
}
