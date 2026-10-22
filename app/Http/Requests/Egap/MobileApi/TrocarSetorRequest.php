<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;

class TrocarSetorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'setor_id' => ['required', 'integer'],
        ];
    }
}
