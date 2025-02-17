<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ButacaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_b' => $this->id_b,
            'id_s' => $this->id_s,
            'sala' => new SalaResource($this->whenLoaded('sala')),
        ];
    }
}
