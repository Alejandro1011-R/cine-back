<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Repositories;

use App\Modules\Ticketing\Domain\Contracts\SeatAvailabilityChecker;
use Illuminate\Support\Facades\DB;

final class EloquentSeatAvailabilityChecker implements SeatAvailabilityChecker
{
    public function hasReservedSeats(int $peliculaId, int $salaId, string $fecha, array $butacaIds): bool
    {
        DB::table('seat_holds')
            ->where('expires_at', '<=', now())
            ->delete();

        $hasConfirmedReservation = DB::table('butacas_reservadas')
            ->where('id_p', $peliculaId)
            ->where('id_s', $salaId)
            ->where('fecha', $fecha)
            ->whereIn('id_b', $butacaIds)
            ->exists();

        if ($hasConfirmedReservation) {
            return true;
        }

        return DB::table('seat_holds')
            ->where('id_p', $peliculaId)
            ->where('id_s', $salaId)
            ->where('fecha', $fecha)
            ->whereIn('id_b', $butacaIds)
            ->where('expires_at', '>', now())
            ->exists();
    }
}
