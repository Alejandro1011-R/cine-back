<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class UpdateUserData
{
    public function __construct(
        public ?string $nombreS,
        public ?string $apellidos,
        public ?string $correo,
        public string $contrasena,
    ) {}
}
