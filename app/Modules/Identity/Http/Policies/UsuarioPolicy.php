<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Policies;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;

final class UsuarioPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ], true);
    }

    public function view(Usuario $user, Usuario $target): bool
    {
        if ($user->ci === $target->ci) {
            return true;
        }

        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        return $user->rol === UserRole::ADMIN->value
            && $this->isOperationalStaff($target)
            && $this->sharesAssignedCine($user, $target);
    }

    public function update(Usuario $user, Usuario $target): bool
    {
        return $user->ci === $target->ci
            || $user->rol === UserRole::SUPER_ADMIN->value;
    }

    public function delete(Usuario $user, Usuario $target): bool
    {
        return $user->rol === UserRole::SUPER_ADMIN->value
            && $user->ci !== $target->ci;
    }

    public function changeRole(Usuario $user): bool
    {
        return $user->rol === UserRole::SUPER_ADMIN->value;
    }

    public function promoteSocio(Usuario $user, Usuario $target): bool
    {
        return $user->ci === $target->ci
            || $user->rol === UserRole::SUPER_ADMIN->value;
    }

    private function isOperationalStaff(Usuario $target): bool
    {
        return in_array($target->rol, [
            UserRole::ADMIN->value,
            UserRole::TAQUILLERO->value,
        ], true);
    }

    private function sharesAssignedCine(Usuario $user, Usuario $target): bool
    {
        return $target->cines()
            ->whereIn('cines.id_c', $user->cines()->select('cines.id_c'))
            ->exists();
    }
}
