<?php

declare(strict_types=1);

namespace Tests\Feature\Ticketing;

use App\Modules\Ticketing\Application\Jobs\SendCompraCancelledNotificationJob;
use App\Modules\Ticketing\Application\Jobs\SendCompraCreatedNotificationJob;
use App\Modules\Ticketing\Application\Listeners\QueueCompraCancelledNotifications;
use App\Modules\Ticketing\Application\Listeners\QueueCompraCreatedNotifications;
use App\Modules\Ticketing\Domain\Events\CompraCancelled;
use App\Modules\Ticketing\Domain\Events\CompraCreated;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CompraNotificationTest extends TestCase
{
    public function test_compra_created_listener_dispatches_notification_job(): void
    {
        Queue::fake();

        (new QueueCompraCreatedNotifications())->handle(new CompraCreated(1, '11111111111'));

        Queue::assertPushed(SendCompraCreatedNotificationJob::class);
    }

    public function test_compra_cancelled_listener_dispatches_notification_job(): void
    {
        Queue::fake();

        (new QueueCompraCancelledNotifications())->handle(new CompraCancelled(1, '11111111111'));

        Queue::assertPushed(SendCompraCancelledNotificationJob::class);
    }
}
