<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles sin asignar permisos
        $roles = [
            'super_admin', // Rol especial con todos los permisos
            'Administrador',
            'Directiva',
            'Gerencia',
            'Administracion Regional',
            'Jefatura',
            'Operativo',
            'Empleado',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Asignar rol Super Admin al usuario admin@admin.com
        $admin = User::where('email', 'admin@admin.com')->firstOrFail();
        $admin->assignRole('super_admin');

        // Asignar rol Empleado a todos los demás usuarios por defecto
        $empleadoRole = Role::where('name', 'Empleado')->first();
        
        User::whereDoesntHave('roles', function($query) {
            $query->where('name', 'super_admin');
        })->each(function ($user) use ($empleadoRole) {
            $user->assignRole($empleadoRole);
        });
    }
}