<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Domain\Contracts;

use Carbon\Carbon;

interface SesionScheduleConflictChecker
{
    public function hasOverlap(Carbon $startTime, int $durationMinutes, int $salaId): bool;
}
