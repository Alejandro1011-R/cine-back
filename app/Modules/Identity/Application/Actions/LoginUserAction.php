<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\LoginData;
use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Hashing\Sha256PasswordHasher;
use App\Modules\Identity\Infrastructure\Persistence\Models\Usuario;

final class LoginUserAction
{
    public function __construct(
        private readonly Sha256PasswordHasher $hasher,
    ) {}

    /**
     * @return array{usuario: Usuario, token: string}|null
     */
    public function handle(LoginData $data): ?array
    {
        $usuario = Usuario::with('cliente')->find($data->ci);

        if ($usuario === null) {
            return null;
        }

        if (!$this->hasher->check($data->contrasena, $usuario->contrasena ?? '')) {
            return null;
        }

        if ($this->hasher->needsRehash($usuario->contrasena ?? '')) {
            $usuario->contrasena = $this->hasher->make($data->contrasena);
            $usuario->save();
        }

        $token = $usuario->createToken('auth-token', [$usuario->rol ?? UserRole::CLIENTE->value]);

        return [
            'usuario' => $usuario,
            'token' => $token->plainTextToken,
        ];
    }
}
