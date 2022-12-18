<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Derivacion;
use Illuminate\Auth\Access\HandlesAuthorization;

class DerivacionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the derivacion can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the derivacion can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Derivacion  $model
     * @return mixed
     */
    public function view(User $user, Derivacion $model)
    {
        return true;
    }

    /**
     * Determine whether the derivacion can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the derivacion can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Derivacion  $model
     * @return mixed
     */
    public function update(User $user, Derivacion $model)
    {
        return true;
    }

    /**
     * Determine whether the derivacion can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Derivacion  $model
     * @return mixed
     */
    public function delete(User $user, Derivacion $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Derivacion  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the derivacion can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Derivacion  $model
     * @return mixed
     */
    public function restore(User $user, Derivacion $model)
    {
        return false;
    }

    /**
     * Determine whether the derivacion can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Derivacion  $model
     * @return mixed
     */
    public function forceDelete(User $user, Derivacion $model)
    {
        return false;
    }
}
