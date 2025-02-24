<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DescuentoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id_d' => $this->id_d,
            'nombre_d' => $this->nombre_d,
            'porciento' => $this->porciento,
        ];
    }
}
