<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Infrastructure\Integrations;

use App\Modules\Ticketing\Application\DTOs\CardPaymentData;
use App\Modules\Ticketing\Application\DTOs\PaymentGatewayResult;
use App\Modules\Ticketing\Domain\Contracts\PaymentGateway;
use Illuminate\Support\Str;

final class FakePaymentGateway implements PaymentGateway
{
    public function charge(CardPaymentData $paymentData): PaymentGatewayResult
    {
        $digits = (string) preg_replace('/\D/', '', $paymentData->cardNumber);

        if (strlen($digits) < 12 || str_ends_with($digits, '0000') || ($paymentData->amount !== null && $paymentData->amount <= 0)) {
            return new PaymentGatewayResult(
                approved: false,
                status: 'declined',
                declineReason: 'Pago rechazado por la pasarela.',
            );
        }

        return new PaymentGatewayResult(
            approved: true,
            reference: 'SIM-' . Str::upper(Str::random(12)),
            status: 'approved',
            brand: $this->detectBrand($digits),
            lastFour: substr($digits, -4),
        );
    }

    private function detectBrand(string $digits): string
    {
        return match (substr($digits, 0, 1)) {
            '4' => 'visa',
            '5' => 'mastercard',
            default => 'card',
        };
    }
}
