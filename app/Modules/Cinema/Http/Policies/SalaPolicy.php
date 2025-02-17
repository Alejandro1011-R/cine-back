<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Policies;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Sala;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;

final class SalaPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true);
    }

    public function view(Usuario $user, Sala $sala): bool
    {
        return $this->belongsToSalaCine($user, $sala, allowTaquillero: true);
    }

    public function create(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value], true);
    }

    public function update(Usuario $user, Sala $sala): bool
    {
        return $this->belongsToSalaCine($user, $sala);
    }

    public function delete(Usuario $user, Sala $sala): bool
    {
        return $this->belongsToSalaCine($user, $sala);
    }

    private function belongsToSalaCine(Usuario $user, Sala $sala, bool $allowTaquillero = false): bool
    {
        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        $allowedRoles = $allowTaquillero
            ? [UserRole::ADMIN->value, UserRole::TAQUILLERO->value]
            : [UserRole::ADMIN->value];

        return in_array($user->rol, $allowedRoles, true)
            && $sala->id_c !== null
            && $user->belongsToCine((int) $sala->id_c);
    }
}
