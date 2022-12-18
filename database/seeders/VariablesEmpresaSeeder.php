<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VariablesEmpresa;

class VariablesEmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        VariablesEmpresa::factory()
            ->count(5)
            ->create();
    }
}
