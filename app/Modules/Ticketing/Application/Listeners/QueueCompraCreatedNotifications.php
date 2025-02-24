<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Listeners;

use App\Modules\Ticketing\Application\Jobs\SendCompraCreatedNotificationJob;
use App\Modules\Ticketing\Domain\Events\CompraCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class QueueCompraCreatedNotifications implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(CompraCreated $event): void
    {
        SendCompraCreatedNotificationJob::dispatch($event->paymentId, $event->customerCi);
    }
}
