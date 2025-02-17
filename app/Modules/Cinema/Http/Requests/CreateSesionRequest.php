<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSesionRequest extends FormRequest
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
        ];
    }
}
