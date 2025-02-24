<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Policies;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use App\Modules\Ticketing\Infrastructure\Persistence\Models\Compra;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de acceso para Compras:
 *  - SuperAdmin  → ve todo
 *  - Admin       → ve/gestiona compras de sus cines asignados
 *  - Taquillero  → ve compras de sus cines asignados
 *  - Cliente/Socio → solo sus propias compras
 */
final class CompraPolicy
{
    /** ¿Puede listar compras? Sí si es staff de algún cine o superadmin. */
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::TAQUILLERO->value,
        ], true);
    }

    /** ¿Puede ver esta compra concreta? */
    public function view(Usuario $user, Compra $compra): bool
    {
        // Siempre puede ver su propia compra
        if ($user->ci === $compra->ci) {
            return true;
        }

        // SuperAdmin ve todo
        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        // Admin/Taquillero solo ven compras de sus cines asignados.
        if (
            in_array($user->rol, [UserRole::ADMIN->value, UserRole::TAQUILLERO->value], true)
        ) {
            return $this->compraPerteneceStaffCines($user, (int) $compra->id_p, (int) $compra->id_s, $compra->fecha);
        }

        return false;
    }

    /** ¿Puede cancelar esta compra? Solo el dueño, Admin del cine o SuperAdmin. */
    public function delete(Usuario $user, Compra $compra): bool
    {
        if ($user->ci === $compra->ci) {
            return true;
        }

        if ($user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        if ($user->rol === UserRole::ADMIN->value) {
            return $this->compraPerteneceStaffCines($user, (int) $compra->id_p, (int) $compra->id_s, $compra->fecha);
        }

        return false;
    }

    /**
     * Verifica si la sesión de la compra está en una sala de un cine asignado al staff.
     * Usamos raw query porque Compra tiene PK compuesta y no tiene una relación Eloquent directa.
     */
    private function compraPerteneceStaffCines(Usuario $user, int $idP, int $idS, mixed $fecha): bool
    {
        return DB::table('sesiones')
            ->join('salas', 'sesiones.id_s', '=', 'salas.id_s')
            ->join('cine_usuario', 'salas.id_c', '=', 'cine_usuario.id_c')
            ->where('sesiones.id_p', $idP)
            ->where('sesiones.id_s', $idS)
            ->where('sesiones.fecha', $fecha)
            ->where('cine_usuario.ci', $user->ci)
            ->exists();
    }
}
