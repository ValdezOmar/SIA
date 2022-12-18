<?php

namespace Database\Seeders;

use App\Models\FilePersonal;
use Illuminate\Database\Seeder;

class FilePersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FilePersonal::factory()
            ->count(5)
            ->create();
    }
}
