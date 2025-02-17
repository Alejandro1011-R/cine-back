<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Infrastructure\Persistence\Repositories;

use App\Modules\Cinema\Domain\Contracts\SesionScheduleConflictChecker;
use App\Modules\Cinema\Infrastructure\Persistence\Models\Sesion;
use Carbon\Carbon;

final class EloquentSesionScheduleConflictChecker implements SesionScheduleConflictChecker
{
    public function hasOverlap(Carbon $startTime, int $durationMinutes, int $salaId): bool
    {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        return Sesion::query()
            ->where('id_s', $salaId)
            ->join('peliculas', 'sesiones.id_p', '=', 'peliculas.id_p')
            ->where(function ($query) use ($startTime, $endTime): void {
                $query
                    ->where(function ($q) use ($startTime, $endTime): void {
                        $q->where('sesiones.fecha', '>=', $startTime)
                            ->where('sesiones.fecha', '<=', $endTime);
                    })
                    ->orWhere(function ($q) use ($startTime, $endTime): void {
                        $q->whereRaw("(sesiones.fecha + (peliculas.duracion || ' minutes')::interval) >= ?", [$startTime])
                            ->whereRaw("(sesiones.fecha + (peliculas.duracion || ' minutes')::interval) <= ?", [$endTime]);
                    })
                    ->orWhere(function ($q) use ($startTime, $endTime): void {
                        $q->where('sesiones.fecha', '<=', $startTime)
                            ->whereRaw("(sesiones.fecha + (peliculas.duracion || ' minutes')::interval) >= ?", [$endTime]);
                    });
            })
            ->exists();
    }
}
