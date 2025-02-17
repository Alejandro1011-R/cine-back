<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Resources;

use App\Modules\Catalog\Http\Resources\PeliculaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SesionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_p' => $this->id_p,
            'id_s' => $this->id_s,
            'fecha' => $this->fecha,
            'pelicula' => new PeliculaResource($this->whenLoaded('pelicula')),
            'sala' => new SalaResource($this->whenLoaded('sala')),
        ];
    }
}
