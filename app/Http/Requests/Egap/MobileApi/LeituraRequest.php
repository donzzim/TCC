<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;

class LeituraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string'],
        ];
    }
}
