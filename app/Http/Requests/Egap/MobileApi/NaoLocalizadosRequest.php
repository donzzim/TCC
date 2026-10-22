<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;

class NaoLocalizadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bem_ids' => ['required', 'array', 'min:1'],
            'bem_ids.*' => ['integer'],
            'justificativa' => ['required', 'string'],
        ];
    }
}
