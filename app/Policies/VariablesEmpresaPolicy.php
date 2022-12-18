<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VariablesEmpresa;
use Illuminate\Auth\Access\HandlesAuthorization;

class VariablesEmpresaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the variablesEmpresa can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the variablesEmpresa can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\VariablesEmpresa  $model
     * @return mixed
     */
    public function view(User $user, VariablesEmpresa $model)
    {
        return true;
    }

    /**
     * Determine whether the variablesEmpresa can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the variablesEmpresa can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\VariablesEmpresa  $model
     * @return mixed
     */
    public function update(User $user, VariablesEmpresa $model)
    {
        return true;
    }

    /**
     * Determine whether the variablesEmpresa can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\VariablesEmpresa  $model
     * @return mixed
     */
    public function delete(User $user, VariablesEmpresa $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\VariablesEmpresa  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the variablesEmpresa can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\VariablesEmpresa  $model
     * @return mixed
     */
    public function restore(User $user, VariablesEmpresa $model)
    {
        return false;
    }

    /**
     * Determine whether the variablesEmpresa can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\VariablesEmpresa  $model
     * @return mixed
     */
    public function forceDelete(User $user, VariablesEmpresa $model)
    {
        return false;
    }
}
