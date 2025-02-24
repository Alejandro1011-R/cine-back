<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WebPaymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id_pg' => $this->id_pg,
            'cantidad' => $this->cantidad,
            'gateway_reference' => $this->gateway_reference,
            'gateway_status' => $this->gateway_status,
            'card_brand' => $this->card_brand,
            'card_last_four' => $this->card_last_four,
        ];
    }
}
