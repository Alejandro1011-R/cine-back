<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Infrastructure\Providers;

use App\Modules\Cinema\Domain\Contracts\SesionScheduleConflictChecker;
use App\Modules\Cinema\Infrastructure\Persistence\Repositories\EloquentSesionScheduleConflictChecker;
use Illuminate\Support\ServiceProvider;

final class CinemaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SesionScheduleConflictChecker::class,
            EloquentSesionScheduleConflictChecker::class,
        );
    }

    public function boot(): void {}
}
