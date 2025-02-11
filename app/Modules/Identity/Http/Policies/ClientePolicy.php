<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Policies;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Support\Facades\DB;

final class ClientePolicy
{
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol, [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ], true);
    }

    public function view(Usuario $user, Cliente $cliente): bool
    {
        if ($user->ci === $cliente->ci || $user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        return $user->rol === UserRole::ADMIN->value
            && $this->clienteTieneComprasEnCinesDelUsuario($user, $cliente);
    }

    public function create(Usuario $user): bool
    {
        return in_array($user->rol, [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value], true);
    }

    public function update(Usuario $user, Cliente $cliente): bool
    {
        if ($user->ci === $cliente->ci || $user->rol === UserRole::SUPER_ADMIN->value) {
            return true;
        }

        return $user->rol === UserRole::ADMIN->value
            && $this->clienteTieneComprasEnCinesDelUsuario($user, $cliente);
    }

    public function delete(Usuario $user, Cliente $cliente): bool
    {
        return $user->rol === UserRole::SUPER_ADMIN->value;
    }

    private function clienteTieneComprasEnCinesDelUsuario(Usuario $user, Cliente $cliente): bool
    {
        return DB::table('compras')
            ->join('sesiones', function ($join): void {
                $join->on('compras.id_p', '=', 'sesiones.id_p')
                    ->on('compras.id_s', '=', 'sesiones.id_s')
                    ->on('compras.fecha', '=', 'sesiones.fecha');
            })
            ->join('salas', 'sesiones.id_s', '=', 'salas.id_s')
            ->join('cine_usuario', 'salas.id_c', '=', 'cine_usuario.id_c')
            ->where('compras.ci', $cliente->ci)
            ->where('cine_usuario.ci', $user->ci)
            ->exists();
    }
}
