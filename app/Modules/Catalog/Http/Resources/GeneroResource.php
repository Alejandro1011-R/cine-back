<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GeneroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_g' => $this->id_g,
            'nombre_g' => $this->nombre_g,
        ];
    }
}
