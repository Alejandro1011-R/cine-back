<?php

declare(strict_types=1);

namespace App\Modules\Cinema\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreButacaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_s' => ['required', 'integer', 'exists:salas,id_s'],
        ];
    }
}
