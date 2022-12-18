<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attacheable;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttacheablePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the attacheable can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the attacheable can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Attacheable  $model
     * @return mixed
     */
    public function view(User $user, Attacheable $model)
    {
        return true;
    }

    /**
     * Determine whether the attacheable can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the attacheable can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Attacheable  $model
     * @return mixed
     */
    public function update(User $user, Attacheable $model)
    {
        return true;
    }

    /**
     * Determine whether the attacheable can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Attacheable  $model
     * @return mixed
     */
    public function delete(User $user, Attacheable $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Attacheable  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the attacheable can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Attacheable  $model
     * @return mixed
     */
    public function restore(User $user, Attacheable $model)
    {
        return false;
    }

    /**
     * Determine whether the attacheable can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\Attacheable  $model
     * @return mixed
     */
    public function forceDelete(User $user, Attacheable $model)
    {
        return false;
    }
}
