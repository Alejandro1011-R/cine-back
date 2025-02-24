<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CompraCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $paymentId,
        public readonly string $customerCi,
    ) {}
}
