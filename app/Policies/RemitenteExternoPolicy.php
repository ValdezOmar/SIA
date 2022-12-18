<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RemitenteExterno;
use Illuminate\Auth\Access\HandlesAuthorization;

class RemitenteExternoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the remitenteExterno can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the remitenteExterno can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\RemitenteExterno  $model
     * @return mixed
     */
    public function view(User $user, RemitenteExterno $model)
    {
        return true;
    }

    /**
     * Determine whether the remitenteExterno can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the remitenteExterno can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\RemitenteExterno  $model
     * @return mixed
     */
    public function update(User $user, RemitenteExterno $model)
    {
        return true;
    }

    /**
     * Determine whether the remitenteExterno can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\RemitenteExterno  $model
     * @return mixed
     */
    public function delete(User $user, RemitenteExterno $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\RemitenteExterno  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the remitenteExterno can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\RemitenteExterno  $model
     * @return mixed
     */
    public function restore(User $user, RemitenteExterno $model)
    {
        return false;
    }

    /**
     * Determine whether the remitenteExterno can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\RemitenteExterno  $model
     * @return mixed
     */
    public function forceDelete(User $user, RemitenteExterno $model)
    {
        return false;
    }
}
