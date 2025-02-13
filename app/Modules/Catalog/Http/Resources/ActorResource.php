<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ActorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_a' => $this->id_a,
            'nombre_a' => $this->nombre_a,
        ];
    }
}
