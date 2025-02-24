<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Persistence\Repositories;

use App\Modules\Ticketing\Domain\Contracts\SeatHoldManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentSeatHoldManager implements SeatHoldManager
{
    public function acquire(int $peliculaId, int $salaId, string $fecha, string $ci, array $butacaIds, int $ttlMinutes = 10): ?string
    {
        $holdToken = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($peliculaId, $salaId, $fecha, $ci, $butacaIds, $ttlMinutes, $holdToken): string|null {
                $this->clearExpired();

                $alreadyReserved = DB::table('butacas_reservadas')
                    ->where('id_p', $peliculaId)
                    ->where('id_s', $salaId)
                    ->where('fecha', $fecha)
                    ->whereIn('id_b', $butacaIds)
                    ->exists();

                if ($alreadyReserved) {
                    return null;
                }

                foreach ($butacaIds as $butacaId) {
                    DB::table('seat_holds')->insert([
                        'hold_token' => $holdToken,
                        'id_p' => $peliculaId,
                        'id_s' => $salaId,
                        'fecha' => $fecha,
                        'ci' => $ci,
                        'id_b' => $butacaId,
                        'expires_at' => now()->addMinutes($ttlMinutes),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $holdToken;
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    public function release(string $holdToken): void
    {
        DB::table('seat_holds')->where('hold_token', $holdToken)->delete();
    }

    private function clearExpired(): void
    {
        DB::table('seat_holds')
            ->where('expires_at', '<=', now())
            ->delete();
    }
}
