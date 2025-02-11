<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Cinema\Http\Resources\CineResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UsuarioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'ci' => $this->ci,
            'nombre_s' => $this->nombre_s,
            'apellidos' => $this->apellidos,
            'puntos' => $this->puntos,
            'codigo' => $this->codigo,
            'rol' => $this->rol,
            'id_c' => $this->id_c,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'cines' => CineResource::collection($this->whenLoaded('cines')),
        ];
    }
}
