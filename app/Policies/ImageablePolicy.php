<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Imageable;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImageablePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the imageable can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the imageable can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Imageable  $model
     * @return mixed
     */
    public function view(User $user, Imageable $model)
    {
        return true;
    }

    /**
     * Determine whether the imageable can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the imageable can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Imageable  $model
     * @return mixed
     */
    public function update(User $user, Imageable $model)
    {
        return true;
    }

    /**
     * Determine whether the imageable can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Imageable  $model
     * @return mixed
     */
    public function delete(User $user, Imageable $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Imageable  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the imageable can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Imageable  $model
     * @return mixed
     */
    public function restore(User $user, Imageable $model)
    {
        return false;
    }

    /**
     * Determine whether the imageable can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Imageable  $model
     * @return mixed
     */
    public function forceDelete(User $user, Imageable $model)
    {
        return false;
    }
}
