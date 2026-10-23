<?php

namespace App\Http\Resources\Egap\MobileApi;

use App\Http\Controllers\Egap\MobileApi\BensController;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property BemMovel $resource
 */
class BemResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $bem = $this->resource;

        $dados = [
            'id' => $bem->id,
            'patrimonio' => $bem->NumPatrimonio,
            'patrimonio_anterior' => $bem->NumerodePatAnterior,
            'tombo_smarapd' => $bem->TomboSmarapd,
            'num_tombo_smarapd' => $bem->NumTomboSmarapd,
            'numero_serie' => $bem->NumerodeSerie,
            'descricao' => $bem->Descricao,
            'descricao_resumida' => $bem->descricaoResumidaBemRef?->Descricao,
            'marca' => $bem->marcaRef?->descricao,
            'modelo' => $bem->modeloRef?->descricao,
            'tipo_bem' => $bem->TipodoBem,
            'estado_conservacao' => $bem->EstadodeConservacao,
            'voltagem' => $bem->Voltagem,
            'situacao_id' => $bem->SituacaoBem,
            'situacao' => $bem->situacaoBemRef?->descricao_completa,
            'situacao_elegivel' => in_array((int) $bem->SituacaoBem, BensController::SITUACOES_ELEGIVEIS, true),
            'unidade_judiciaria' => [
                'id' => $bem->UnidadeJudiciaria,
                'nome' => $bem->unidadeJudiciariaRef?->setor,
            ],
            'setor' => [
                'id' => $bem->Setor,
                'nome' => $bem->setorRef?->setor,
            ],
            'complemento_setor' => [
                'id' => $bem->ComplementoSetor,
                'nome' => $bem->complementoSetorRef?->descricao,
            ],
            'andar_setor' => $bem->AndarSetor,
            'valor_aquisicao' => $bem->ValorAquisicao,
            'valor' => $bem->Valor,
            'data_incorporacao' => $bem->getRawOriginal('DatadeIncorporacao'),
            'data_cadastro' => $bem->getRawOriginal('DataCadastro'),
            'data_baixa' => $bem->getRawOriginal('DataBaixa'),
            'processo_baixa' => $bem->ProcessoBaixa,
            'numero_processo' => $bem->numero_processo,
            'nota_empenho' => $bem->nota_empenho,
            'nota_liquidacao' => $bem->nota_liquidacao,
            'data_liquidacao' => $bem->getRawOriginal('data_liquidacao'),
            'observacao' => $bem->Observacao,
        ];

        // Atributo definido só no show(): na listagem todos os bens são do setor.
        if (array_key_exists('pertence_ao_setor', $bem->getAttributes())) {
            $dados['pertence_ao_setor'] = (bool) $bem->getAttribute('pertence_ao_setor');
        }

        return $dados;
    }
}
