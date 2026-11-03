<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NaoLocalizadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ou a lista de bens, ou "todos os pendentes do setor" (o servidor resolve a lista)
            'bem_ids' => [Rule::requiredIf(! $this->boolean('todos_pendentes')), 'array', 'min:1'],
            'bem_ids.*' => ['integer'],
            'todos_pendentes' => ['sometimes', 'boolean'],
            // vai para a observação do item, que é varchar(255)
            'justificativa' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<int, int>|null  null = todos os pendentes */
    public function bemIds(): ?array
    {
        return $this->boolean('todos_pendentes') ? null : $this->validated('bem_ids');
    }
}
