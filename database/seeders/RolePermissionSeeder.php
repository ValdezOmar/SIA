<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            'leer',
            'escribir',
            'modificar',
            'borrar'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Crear roles y asignar permisos
        $roles = [
            'Administrador' => ['leer', 'escribir', 'modificar', 'borrar'],
            'Directiva' => ['leer', 'escribir', 'modificar'],
            'Gerencia' => ['leer', 'escribir', 'modificar'],
            'Administracion Regional' => ['leer', 'escribir', 'modificar'],
            'Jefatura' => ['leer', 'escribir', 'modificar'],
            'Operativo' => ['leer', 'escribir'],
            'Empleado' => ['leer'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }

        // Asignar rol Administrador al usuario admin@admin.com
        $admin = User::where('email', 'admin@admin.com')->first();
        if ($admin) {
            $admin->assignRole('Administrador');
        }

        // Asignar rol Empleado a todos los usuarios por defecto
        $empleadoRole = Role::where('name', 'Empleado')->first();
        User::all()->each(function ($user) use ($empleadoRole) {
            if (!$user->hasAnyRole()) {
                $user->assignRole($empleadoRole);
            }
        });
    }
}