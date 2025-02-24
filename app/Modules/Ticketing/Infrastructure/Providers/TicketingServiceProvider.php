<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Providers;

use App\Modules\Ticketing\Domain\Contracts\PaymentGateway;
use App\Modules\Ticketing\Domain\Contracts\SeatAvailabilityChecker;
use App\Modules\Ticketing\Domain\Contracts\SeatHoldManager;
use App\Modules\Ticketing\Infrastructure\Integrations\FakePaymentGateway;
use App\Modules\Ticketing\Infrastructure\Persistence\Repositories\EloquentSeatAvailabilityChecker;
use App\Modules\Ticketing\Infrastructure\Persistence\Repositories\EloquentSeatHoldManager;
use Illuminate\Support\ServiceProvider;

final class TicketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SeatAvailabilityChecker::class,
            EloquentSeatAvailabilityChecker::class,
        );

        $this->app->bind(
            PaymentGateway::class,
            FakePaymentGateway::class,
        );

        $this->app->bind(
            SeatHoldManager::class,
            EloquentSeatHoldManager::class,
        );
    }

    public function boot(): void {}
}
