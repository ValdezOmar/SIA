<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FilePersonal;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePersonalPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the filePersonal can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the filePersonal can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\FilePersonal  $model
     * @return mixed
     */
    public function view(User $user, FilePersonal $model)
    {
        return true;
    }

    /**
     * Determine whether the filePersonal can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the filePersonal can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\FilePersonal  $model
     * @return mixed
     */
    public function update(User $user, FilePersonal $model)
    {
        return true;
    }

    /**
     * Determine whether the filePersonal can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\FilePersonal  $model
     * @return mixed
     */
    public function delete(User $user, FilePersonal $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\FilePersonal  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the filePersonal can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\FilePersonal  $model
     * @return mixed
     */
    public function restore(User $user, FilePersonal $model)
    {
        return false;
    }

    /**
     * Determine whether the filePersonal can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\FilePersonal  $model
     * @return mixed
     */
    public function forceDelete(User $user, FilePersonal $model)
    {
        return false;
    }
}
