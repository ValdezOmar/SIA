<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RemitenteExterno;

class RemitenteExternoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RemitenteExterno::factory()
            ->count(5)
            ->create();
    }
}
