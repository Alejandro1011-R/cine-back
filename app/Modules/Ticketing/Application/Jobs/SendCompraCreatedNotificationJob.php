<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendCompraCreatedNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $paymentId,
        public readonly string $customerCi,
    ) {}

    public function handle(): void
    {
        Log::info('Compra creada: notificación enviada.', [
            'payment_id' => $this->paymentId,
            'customer_ci' => $this->customerCi,
        ]);
    }
}
