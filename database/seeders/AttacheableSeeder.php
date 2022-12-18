<?php

namespace Database\Seeders;

use App\Models\Attacheable;
use Illuminate\Database\Seeder;

class AttacheableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Attacheable::factory()
            ->count(5)
            ->create();
    }
}
