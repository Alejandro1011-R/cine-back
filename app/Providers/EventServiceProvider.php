<?php

namespace App\Providers;

use App\Modules\Ticketing\Application\Listeners\QueueCompraCancelledNotifications;
use App\Modules\Ticketing\Application\Listeners\QueueCompraCreatedNotifications;
use App\Modules\Ticketing\Domain\Events\CompraCancelled;
use App\Modules\Ticketing\Domain\Events\CompraCreated;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CompraCreated::class => [
            QueueCompraCreatedNotifications::class,
        ],
        CompraCancelled::class => [
            QueueCompraCancelledNotifications::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
