<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PuntoPagoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id_pg' => $this->id_pg,
            'gastados' => $this->gastados,
        ];
    }
}
