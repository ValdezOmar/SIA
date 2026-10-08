<?php

namespace App\Policies\Sistema;

use App\Models\Sistema\Auditoria;
use App\Models\User;

class AuditoriaPolicy
{
    public const PERMISO_CONSULTAR = 'view_any_auditoria';

    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can(self::PERMISO_CONSULTAR)
            || $user->can('ver_todas_auditorias');
    }

    public function view(User $user, Auditoria $registro): bool
    {
        return $this->viewAny($user) && Auditoria::query()->visiblePara($user)->whereKey($registro->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Auditoria $registro): bool
    {
        return false;
    }

    public function delete(User $user, Auditoria $registro): bool
    {
        return false;
    }
}
