<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Policies;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;

final class SesionPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true);
    }

    public function view(Usuario $user, Sesion $sesion): bool
    {
        return $this->belongsToSesionCine($user, $sesion, allowTaquillero: true);
    }

    public function create(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value], true);
    }

    public function delete(Usuario $user, Sesion $sesion): bool
    {
        return $this->belongsToSesionCine($user, $sesion);
    }

    private function belongsToSesionCine(Usuario $user, Sesion $sesion, bool $allowTaquillero = false): bool
    {
        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        $allowedRoles = $allowTaquillero
            ? [UserRole::ADMIN->value, UserRole::TAQUILLERO->value]
            : [UserRole::ADMIN->value];

        $sesion->loadMissing('sala');

        return in_array($user->rol, $allowedRoles, true)
            && $sesion->sala?->id_c !== null
            && $user->belongsToCine((int) $sesion->sala->id_c);
    }
}
