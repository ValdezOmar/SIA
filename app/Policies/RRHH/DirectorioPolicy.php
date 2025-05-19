<?php

namespace App\Policies\RRHH;

use App\Models\RRHH\Empleado;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DirectorioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Empleado $empleado): bool
    {
         // Si es empleado, solo puede ver su propio perfil
        // if ($user->hasRole('Empleado')) {
        //     return $user->email === $empleado->correo_corporativo;
        // }
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Empleado $empleado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Empleado $empleado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Empleado $empleado): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Empleado $empleado): bool
    {
        return false;
    }
}
