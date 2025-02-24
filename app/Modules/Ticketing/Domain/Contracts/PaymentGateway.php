<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Domain\Contracts;

use App\Modules\Ticketing\Application\DTOs\CardPaymentData;
use App\Modules\Ticketing\Application\DTOs\PaymentGatewayResult;

interface PaymentGateway
{
    public function charge(CardPaymentData $paymentData): PaymentGatewayResult;
}
