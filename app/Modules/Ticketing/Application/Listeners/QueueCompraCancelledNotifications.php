<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Listeners;

use App\Modules\Ticketing\Application\Jobs\SendCompraCancelledNotificationJob;
use App\Modules\Ticketing\Domain\Events\CompraCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;

final class QueueCompraCancelledNotifications implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(CompraCancelled $event): void
    {
        SendCompraCancelledNotificationJob::dispatch($event->paymentId, $event->customerCi);
    }
}
