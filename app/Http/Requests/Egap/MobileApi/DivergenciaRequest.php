<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DivergenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bem_id' => ['nullable', 'integer'],
            'codigo' => ['nullable', 'string'],
            'campos' => ['nullable', 'array'],
            'campos.*' => ['string'],
            // valor encontrado por campo: {"Marca": "Dell", "Número de série": "ABC123"}
            'valores' => ['nullable', 'array'],
            'valores.*' => ['nullable', 'string', 'max:255'],
            // a observação do item é varchar(255)
            'observacao' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (empty($this->input('bem_id')) && empty($this->input('codigo'))) {
                    $validator->errors()->add('bem_id', 'Informe o bem ou o código patrimonial.');
                }
            },
        ];
    }
}
