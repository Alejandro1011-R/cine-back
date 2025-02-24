<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PagoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id_pg' => $this->id_pg,
            'efectivo' => new EfectivoResource($this->whenLoaded('efectivo')),
            'punto' => new PuntoPagoResource($this->whenLoaded('punto')),
            'web_payment' => new WebPaymentResource($this->whenLoaded('webPayment')),
            'compra' => new CompraResource($this->whenLoaded('compra')),
        ];
    }
}
