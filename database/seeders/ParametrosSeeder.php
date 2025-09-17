<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('conf_parametros')->insert([
            'logo_path' => '/images/logo.png',
            'favicon_path' => '/images/favicon.ico',
            'fondo_path' => '/images/fondo.jpg',
            'color_principal' => '#009BA4',
            'google_activo' => false,
            'google_client_id' => null,
            'google_client_secret' => null,
            'google_redirect_uri' => null,
            'login_nativo' => true,
            'timezone' => 'America/La_Paz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}