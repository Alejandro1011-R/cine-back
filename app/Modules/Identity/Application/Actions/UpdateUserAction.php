<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\UpdateUserData;
use App\Modules\Identity\Infrastructure\Hashing\Sha256PasswordHasher;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    public function __construct(
        private readonly Sha256PasswordHasher $hasher,
    ) {}

    public function handle(string $ci, UpdateUserData $data): ?Usuario
    {
        return DB::transaction(function () use ($ci, $data): ?Usuario {
            $usuario = Usuario::find($ci);
            $cliente = Cliente::find($ci);

            if ($usuario === null || $cliente === null) {
                return null;
            }

            $cliente->correo = $data->correo;
            $cliente->save();

            $usuario->nombre_s = $data->nombreS;
            $usuario->apellidos = $data->apellidos;
            $usuario->contrasena = $this->hasher->make($data->contrasena);
            $usuario->save();

            $usuario->load('cliente');

            return $usuario;
        });
    }
}
