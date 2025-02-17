<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ];
    }
}
