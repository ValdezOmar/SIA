<?php

namespace Database\Seeders;

use App\Models\Derivacion;
use Illuminate\Database\Seeder;

class DerivacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Derivacion::factory()
            ->count(5)
            ->create();
    }
}
