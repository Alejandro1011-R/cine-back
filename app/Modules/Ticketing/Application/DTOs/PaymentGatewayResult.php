<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\DTOs;

final readonly class PaymentGatewayResult
{
    public function __construct(
        public bool $approved,
        public ?string $reference = null,
        public ?string $status = null,
        public ?string $brand = null,
        public ?string $lastFour = null,
        public ?string $declineReason = null,
    ) {}
}
