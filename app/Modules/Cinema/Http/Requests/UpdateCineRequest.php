<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ];
    }
}
