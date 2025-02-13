<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PeliculaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_p' => $this->id_p,
            'sinopsis' => $this->sinopsis,
            'anno' => $this->anno,
            'nacionalidad' => $this->nacionalidad,
            'duracion' => $this->duracion,
            'titulo' => $this->titulo,
            'imagen' => $this->imagen,
            'trailer' => $this->trailer,
            'actores' => $this->whenLoaded('actores', fn () => $this->actores->pluck('nombre_a')->values()->all()),
            'generos' => $this->whenLoaded('generos', fn () => $this->generos->pluck('nombre_g')->values()->all()),
        ];
    }
}
