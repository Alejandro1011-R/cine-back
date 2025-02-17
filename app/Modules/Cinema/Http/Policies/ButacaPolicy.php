<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Policies;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Butaca;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;

final class ButacaPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true);
    }

    public function view(Usuario $user, Butaca $butaca): bool
    {
        return $this->belongsToButacaCine($user, $butaca, allowTaquillero: true);
    }

    public function create(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value], true);
    }

    public function update(Usuario $user, Butaca $butaca): bool
    {
        return $this->belongsToButacaCine($user, $butaca);
    }

    public function delete(Usuario $user, Butaca $butaca): bool
    {
        return $this->belongsToButacaCine($user, $butaca);
    }

    private function belongsToButacaCine(Usuario $user, Butaca $butaca, bool $allowTaquillero = false): bool
    {
        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        $allowedRoles = $allowTaquillero
            ? [UserRole::ADMIN->value, UserRole::TAQUILLERO->value]
            : [UserRole::ADMIN->value];

        $butaca->loadMissing('sala');

        return in_array($user->rol, $allowedRoles, true)
            && $butaca->sala?->id_c !== null
            && $user->belongsToCine((int) $butaca->sala->id_c);
    }
}
