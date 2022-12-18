<?php

namespace Database\Seeders;

use App\Models\Imageable;
use Illuminate\Database\Seeder;

class ImageableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Imageable::factory()
            ->count(5)
            ->create();
    }
}
