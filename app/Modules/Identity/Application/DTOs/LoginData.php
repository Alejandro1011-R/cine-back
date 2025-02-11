<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

final readonly class LoginData
{
    public function __construct(
        public string $ci,
        public string $contrasena,
    ) {}
}
