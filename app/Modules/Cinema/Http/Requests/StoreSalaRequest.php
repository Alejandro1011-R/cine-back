<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_c' => ['nullable', 'integer', 'exists:cines,id_c'],
            'capacidad' => ['nullable', 'integer'],
        ];
    }
}
