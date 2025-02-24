<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Enums;

enum TipoCompra: string
{
    case TARJETA = 'Tarjeta';
    case EFECTIVO = 'Efectivo';
    case PUNTOS = 'Puntos';
}
