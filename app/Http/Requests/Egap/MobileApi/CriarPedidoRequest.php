<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;

class CriarPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:consumo,permanente'],
            'complemento_setor_id' => ['required', 'integer'],
            'justificativa' => ['nullable', 'string', 'max:500'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.material_id' => ['required', 'integer'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1', 'max:5000'],
            'itens.*.tipo_atendimento' => ['nullable', 'in:adicao,substituicao'],
            'itens.*.justificativa' => ['nullable', 'string', 'max:500'],
            'itens.*.patrimonio_substituido' => ['nullable', 'string', 'max:120'],
        ];
    }
}
