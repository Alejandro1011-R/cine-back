<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Policies;

use App\Modules\Cinema\Infrastructure\Persistence\Models\Cine;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;

/**
 * Reglas de acceso para la gestión de Cines (B2B):
 *  - SuperAdmin  → gestiona todos los cines
 *  - Admin       → solo gestiona sus cines asignados
 *  - Resto       → sin acceso de escritura
 */
final class CinePolicy
{
    /** ¿Puede listar cines? */
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ], true);
    }

    /** ¿Puede ver este cine en detalle? */
    public function view(Usuario $user, Cine $cine): bool
    {
        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        return $user->rol === UserRole::ADMIN->value
            && $user->belongsToCine((int) $cine->id_c);
    }

    /** ¿Puede crear un cine? Solo SuperAdmin. */
    public function create(Usuario $user): bool
    {
        return $user->rol === UserRole::SUPER_ADMIN->value;
    }

    /** ¿Puede actualizar este cine? SuperAdmin o Admin del cine. */
    public function update(Usuario $user, Cine $cine): bool
    {
        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        return $user->rol === UserRole::ADMIN->value
            && $user->belongsToCine((int) $cine->id_c);
    }

    /** ¿Puede eliminar este cine? Solo SuperAdmin. */
    public function delete(Usuario $user, Cine $cine): bool
    {
        return $user->rol === UserRole::SUPER_ADMIN->value;
    }
}
