<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePeliculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sinopsis' => ['nullable', 'string'],
            'anno' => ['nullable', 'integer'],
            'nacionalidad' => ['nullable', 'integer'],
            'duracion' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:50'],
            'imagen' => ['nullable', 'string'],
            'trailer' => ['nullable', 'string'],
            'actor_ids' => ['nullable', 'array'],
            'actor_ids.*' => ['integer', 'exists:actores,id_a'],
            'genero_ids' => ['nullable', 'array'],
            'genero_ids.*' => ['integer', 'exists:generos,id_g'],
        ];
    }
}
