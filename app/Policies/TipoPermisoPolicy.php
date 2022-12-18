<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TipoPermiso;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoPermisoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the tipoPermiso can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tipoPermiso can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoPermiso  $model
     * @return mixed
     */
    public function view(User $user, TipoPermiso $model)
    {
        return true;
    }

    /**
     * Determine whether the tipoPermiso can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tipoPermiso can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoPermiso  $model
     * @return mixed
     */
    public function update(User $user, TipoPermiso $model)
    {
        return true;
    }

    /**
     * Determine whether the tipoPermiso can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoPermiso  $model
     * @return mixed
     */
    public function delete(User $user, TipoPermiso $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoPermiso  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tipoPermiso can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoPermiso  $model
     * @return mixed
     */
    public function restore(User $user, TipoPermiso $model)
    {
        return false;
    }

    /**
     * Determine whether the tipoPermiso can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoPermiso  $model
     * @return mixed
     */
    public function forceDelete(User $user, TipoPermiso $model)
    {
        return false;
    }
}
