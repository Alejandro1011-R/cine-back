<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SalaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_s' => $this->id_s,
            'id_c' => $this->id_c,
            'capacidad' => $this->capacidad,
            'cine' => new CineResource($this->whenLoaded('cine')),
            'butacas' => ButacaResource::collection($this->whenLoaded('butacas')),
            'sesiones' => SesionResource::collection($this->whenLoaded('sesiones')),
        ];
    }
}
