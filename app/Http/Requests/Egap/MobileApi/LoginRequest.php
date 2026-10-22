<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
