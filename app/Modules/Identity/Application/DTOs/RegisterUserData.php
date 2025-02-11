<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class RegisterUserData
{
    public function __construct(
        public string $ci,
        public ?string $nombreS,
        public ?string $apellidos,
        public ?string $correo,
        public string $contrasena,
    ) {}
}
