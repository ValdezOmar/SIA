<?php

namespace Database\Seeders;

use App\Models\CiteInterno;
use Illuminate\Database\Seeder;

class CiteInternoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CiteInterno::factory()
            ->count(5)
            ->create();
    }
}
