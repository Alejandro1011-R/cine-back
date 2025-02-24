<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Resources;

use App\Modules\Identity\Http\Resources\ClienteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeDetails = $request->boolean('include_details');

        return [
            'id_p' => $this->id_p,
            'id_s' => $this->id_s,
            'fecha' => $this->fecha,
            'ci' => $this->ci,
            'id_pg' => $this->id_pg,
            'tipo' => $this->tipo,
            'fecha_de_compra' => $this->fecha_de_compra,
            'medio_ad' => $this->medio_ad,
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
            'pago' => new PagoResource($this->whenLoaded('pago')),
            'butacas' => $this->when($includeDetails, fn () => $this->butacas),
            'descuentos' => $this->when($includeDetails, fn () => $this->descuentos),
        ];
    }
}
