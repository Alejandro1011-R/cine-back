<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Application\DTOs;

use Carbon\Carbon;

final readonly class CreateSesionData
{
    public function __construct(
        public int $idP,
        public int $idS,
        public Carbon $fecha,
    ) {}
}
