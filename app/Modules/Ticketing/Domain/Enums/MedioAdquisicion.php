<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Enums;

enum MedioAdquisicion: string
{
    case WEB = 'Web';
    case TAQUILLA = 'Taquilla';
}
