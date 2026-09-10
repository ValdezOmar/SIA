<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('conf_parametros')->insert([
            'logo_path' => '/images/logo.png',
            'favicon_path' => '/images/favicon.ico',
            'fondo_path' => '/images/fondo.jpg',
            'color_principal' => '#009BA4',
            'color_secundario' => '#3066BE',
            'escala_interfaz' => '88%',
            'estilo_login' => 'cristal',
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
