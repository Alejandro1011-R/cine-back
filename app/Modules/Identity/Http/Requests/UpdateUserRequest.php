<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'size:11'],
            'nombre_s' => ['nullable', 'string', 'max:50'],
            'apellidos' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'string', 'email', 'max:256'],
            'contrasena' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
