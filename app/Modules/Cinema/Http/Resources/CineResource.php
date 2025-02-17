<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_c' => $this->id_c,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'salas' => SalaResource::collection($this->whenLoaded('salas')),
        ];
    }
}
