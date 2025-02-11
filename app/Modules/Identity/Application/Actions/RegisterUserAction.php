<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\RegisterUserData;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Hashing\Sha256PasswordHasher;
use App\Modules\Identity\Infrastructure\Persistence\Models\Cliente;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterUserAction
{
    public function __construct(
        private readonly Sha256PasswordHasher $hasher,
    ) {}

    public function handle(RegisterUserData $data): Usuario
    {
        return DB::transaction(function () use ($data): Usuario {
            // Find or create cliente
            $cliente = Cliente::find($data->ci);
            if ($cliente === null) {
                $cliente = Cliente::create([
                    'ci' => $data->ci,
                    'correo' => $data->correo,
                    'confiabilidad' => true,
                ]);
            }

            // Generate unique code
            $codigo = $this->generateUniqueCode();

            $usuario = new Usuario();
            $usuario->ci = $data->ci;
            $usuario->nombre_s = $data->nombreS;
            $usuario->apellidos = $data->apellidos;
            $usuario->puntos = 0;
            $usuario->rol = UserRole::CLIENTE->value;
            $usuario->codigo = $codigo;
            $usuario->contrasena = $this->hasher->make($data->contrasena);
            $usuario->save();

            $usuario->load('cliente');

            return $usuario;
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(11);
        } while (Usuario::where('codigo', $code)->exists());

        return $code;
    }
}
