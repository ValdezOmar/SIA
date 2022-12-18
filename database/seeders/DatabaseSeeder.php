<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Adding an admin user
        $user = \App\Models\User::factory()
            ->count(1)
            ->create([
                'email' => 'admin@admin.com',
                'password' => \Hash::make('admin'),
            ]);

        $this->call(AreaSeeder::class);
        $this->call(AttacheableSeeder::class);
        $this->call(CargoSeeder::class);
        $this->call(CiteInternoSeeder::class);
        $this->call(DerivacionSeeder::class);
        $this->call(FilePersonalSeeder::class);
        $this->call(ImageableSeeder::class);
        $this->call(PermisosSeeder::class);
        $this->call(RemitenteExternoSeeder::class);
        $this->call(TipoDocumentoSeeder::class);
        $this->call(TipoPermisoSeeder::class);
        $this->call(TramiteSeeder::class);
        $this->call(UserSeeder::class);
        //$this->call(VariablesEmpresaSeeder::class);
    }
}
