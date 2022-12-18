<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tramite;
use Illuminate\Auth\Access\HandlesAuthorization;

class TramitePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the tramite can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tramite can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Tramite  $model
     * @return mixed
     */
    public function view(User $user, Tramite $model)
    {
        return true;
    }

    /**
     * Determine whether the tramite can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tramite can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Tramite  $model
     * @return mixed
     */
    public function update(User $user, Tramite $model)
    {
        return true;
    }

    /**
     * Determine whether the tramite can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Tramite  $model
     * @return mixed
     */
    public function delete(User $user, Tramite $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Tramite  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tramite can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Tramite  $model
     * @return mixed
     */
    public function restore(User $user, Tramite $model)
    {
        return false;
    }

    /**
     * Determine whether the tramite can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Tramite  $model
     * @return mixed
     */
    public function forceDelete(User $user, Tramite $model)
    {
        return false;
    }
}
