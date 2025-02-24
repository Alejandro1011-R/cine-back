<?php

declare(strict_types=1);

namespace App\Modules\Ticketing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompraByTarjetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_p' => ['required', 'integer'],
            'id_s' => ['required', 'integer'],
            'fecha' => ['required', 'date'],
            'ci' => ['required', 'string', 'size:11'],
            'cantidad' => ['nullable', 'numeric'],
            'codigo_t' => ['required', 'string', 'max:18'],
            'fecha_de_compra' => ['required', 'date'],
            'butaca_ids' => ['required', 'array', 'min:1'],
            'butaca_ids.*' => ['integer'],
            'descuento_ids' => ['nullable', 'array'],
            'descuento_ids.*' => ['integer'],
        ];
    }
}
