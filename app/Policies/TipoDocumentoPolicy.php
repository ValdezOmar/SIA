<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TipoDocumento;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoDocumentoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the tipoDocumento can view any models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tipoDocumento can view the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoDocumento  $model
     * @return mixed
     */
    public function view(User $user, TipoDocumento $model)
    {
        return true;
    }

    /**
     * Determine whether the tipoDocumento can create models.
     *
     * @param  App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tipoDocumento can update the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoDocumento  $model
     * @return mixed
     */
    public function update(User $user, TipoDocumento $model)
    {
        return true;
    }

    /**
     * Determine whether the tipoDocumento can delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoDocumento  $model
     * @return mixed
     */
    public function delete(User $user, TipoDocumento $model)
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoDocumento  $model
     * @return mixed
     */
    public function deleteAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the tipoDocumento can restore the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoDocumento  $model
     * @return mixed
     */
    public function restore(User $user, TipoDocumento $model)
    {
        return false;
    }

    /**
     * Determine whether the tipoDocumento can permanently delete the model.
     *
     * @param  App\Models\User  $user
     * @param  App\Models\TipoDocumento  $model
     * @return mixed
     */
    public function forceDelete(User $user, TipoDocumento $model)
    {
        return false;
    }
}
