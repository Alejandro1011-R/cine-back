<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Application\DTOs;

final readonly class CardPaymentData
{
    public function __construct(
        public string $cardNumber,
        public ?float $amount,
        public string $customerCi,
    ) {}
}
