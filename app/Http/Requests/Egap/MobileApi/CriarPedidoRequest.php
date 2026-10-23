<?php

namespace App\Http\Requests\Egap\MobileApi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mesmas regras do carrinho do Ambiente Externo: no consumo a justificativa é
 * do pedido; no permanente, de cada item, junto do tipo de atendimento e do
 * patrimônio substituído (quando for substituição).
 */
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
            'justificativa' => ['required_if:tipo,consumo', 'nullable', 'string', 'max:300'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.material_id' => ['required', 'integer'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1', 'max:5000'],
            'itens.*.tipo_atendimento' => ['required_if:tipo,permanente', 'nullable', 'in:adicao,substituicao'],
            'itens.*.justificativa' => ['required_if:tipo,permanente', 'nullable', 'string', 'max:300'],
            'itens.*.patrimonio_substituido' => ['required_if:itens.*.tipo_atendimento,substituicao', 'nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'justificativa.required_if' => 'Informe a justificativa do pedido.',
            'itens.*.tipo_atendimento.required_if' => 'Selecione adição ou substituição.',
            'itens.*.justificativa.required_if' => 'Informe a justificativa do material.',
            'itens.*.patrimonio_substituido.required_if' => 'Informe o patrimônio que será substituído.',
        ];
    }
}
