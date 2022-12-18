<?php

namespace Database\Seeders;

use App\Models\Permisos;
use Illuminate\Database\Seeder;

class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permisos::factory()
            ->count(5)
            ->create();
    }
}
