<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Permisos;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermisosPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the permisos can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the permisos can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Permisos  $model
     * @return mixed
     */
    public function view(User $user, Permisos $model)
    {
        return true;
    }

    /**
     * Determine whether the permisos can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the permisos can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Permisos  $model
     * @return mixed
     */
    public function update(User $user, Permisos $model)
    {
        return true;
    }

    /**
     * Determine whether the permisos can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Permisos  $model
     * @return mixed
     */
    public function delete(User $user, Permisos $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Permisos  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the permisos can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Permisos  $model
     * @return mixed
     */
    public function restore(User $user, Permisos $model)
    {
        return false;
    }

    /**
     * Determine whether the permisos can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Permisos  $model
     * @return mixed
     */
    public function forceDelete(User $user, Permisos $model)
    {
        return false;
    }
}
