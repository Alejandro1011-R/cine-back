<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum UserRole: string
{
    case CLIENTE    = 'Cliente';
    case SOCIO      = 'Socio';
    case TAQUILLERO = 'Taquillero';
    case ADMIN      = 'Admin';
    /** Puede gestionar todos los cines (B2B global). */
    case SUPER_ADMIN = 'SuperAdmin';
}
