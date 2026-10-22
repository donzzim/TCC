<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Support;

use App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\PlanoContratacao\BemServico;
use App\Models\egap\PlanoContratacao\HistoricoPlano;
use App\Models\egap\PlanoContratacao\LimitePlano;
use App\Models\egap\PlanoContratacao\PlanoContratacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Concentra a escrita de um Plano de Contratação e seus itens, reproduzindo os
 * blocos do legado `pla_novasolicitacao.api.php` (criação) e
 * `pla_planodecontratacao.api.php` (`update_planocontratacao`) numa única
 * transação por operação.
 *
 * O consumo de limite NÃO é gravado — {@see LimitePlano}
 * calcula o disponível a partir dos planos já enviados.
 */
class PlanoWriter
{
    /**
     * Colunas candidatas para um item (schema `pla_bensservicos`). São filtradas
     * pelas que realmente existem no banco, porque dumps mais antigos não têm
     * `cod_item` / `tipo_item` / `valor_desembolsado_ano_pca`.
     *
     * @var array<int, string>
     */
    private const CAMPOS_ITEM = [
        'id_naturezadespesa', 'id_planoorcamentario', 'nome', 'num_processo_sei',
        'cod_item', 'tipo_item', 'classificacao', 'prioridade', 'quantidade',
        'valor1', 'valor2', 'valor_global', 'valor_desembolsado_ano_pca',
        'justificativa', 'data_estimada', 'data_inicio', 'data_executada',
        'renovacao_contrato', 'sus_acess', 'obj_estrategico', 'ini_estrategica',
    ];

    /** @var array<int, string>|null */
    private ?array $colunasItem = null;

    /**
     * Cria um novo plano + itens na etapa informada.
     *
     * @param  array{classificacao: string, unidade_gestora: string, cd_coordenadoria: int, ano_execucao?: int}  $cabecalho
     * @param  array<int, array<string, mixed>>  $itens
     */
    public function criar(array $cabecalho, array $itens, SituacaoPlano $etapa, ?int $usuarioId): PlanoContratacao
    {
        return DB::connection('egap')->transaction(function () use ($cabecalho, $itens, $etapa, $usuarioId): PlanoContratacao {
            $coordenadoria = (int) $cabecalho['cd_coordenadoria'];

            $plano = PlanoContratacao::create([
                'id_solicitante' => $usuarioId,
                'id_etapa' => $etapa->value,
                'classificacao' => $cabecalho['classificacao'],
                'unidade_gestora' => $cabecalho['unidade_gestora'],
                'cd_coordenadoria' => $coordenadoria,
                'secretaria_assessoria' => Setores::query()->whereKey($coordenadoria)->value('codigopai') ?: $coordenadoria,
                'preco_total' => $this->somaItens($itens),
                'ano_execucao' => $cabecalho['ano_execucao'] ?? PlanoContratacaoContexto::anoExecucaoPadrao(),
            ]);

            foreach ($itens as $item) {
                $plano->itens()->create([
                    ...$this->normalizarItem($item),
                    'id_etapa' => $etapa->value,
                ]);
            }

            HistoricoPlano::registrar($plano->id, $etapa->value, $usuarioId);

            return $plano;
        });
    }

    /**
     * Atualiza um plano devolvido/em revisão: aplica edições nos itens, remove os
     * marcados, cria os novos, recalcula `preco_total` e reenvia
     * (legado: `update_planocontratacao` com `status = 6`).
     *
     * @param  array<int, array<string, mixed>>  $itens  cada item pode ter `id` (existente) ou não (novo)
     * @param  array<int, int>  $idsRemover
     */
    public function atualizarEReenviar(PlanoContratacao $plano, array $itens, array $idsRemover, ?int $usuarioId): PlanoContratacao
    {
        return DB::connection('egap')->transaction(function () use ($plano, $itens, $idsRemover, $usuarioId): PlanoContratacao {
            $etapa = SituacaoPlano::EmAnalise->value;

            if ($idsRemover !== []) {
                BemServico::query()
                    ->where('id_planocontratacao', $plano->id)
                    ->whereIn('id', $idsRemover)
                    ->delete();
            }

            foreach ($itens as $item) {
                $dados = [...$this->normalizarItem($item), 'id_etapa' => $etapa];

                if (filled($item['id'] ?? null)) {
                    BemServico::query()
                        ->where('id_planocontratacao', $plano->id)
                        ->whereKey($item['id'])
                        ->first()
                        ?->update($dados);

                    continue;
                }

                $plano->itens()->create($dados);
            }

            $plano->update(['id_etapa' => $etapa]);
            $plano->refresh()->recalcularPrecoTotal();

            HistoricoPlano::registrar($plano->id, $etapa, $usuarioId);

            return $plano;
        });
    }

    /**
     * Confirma a contratação dos itens: grava data executada / SEI e move os
     * itens para Validado (legado: `confirmando_datas_planocontratacao`).
     *
     * @param  array<int, array{id: int, data_executada?: string|null, num_processo_sei?: string|null}>  $itens
     */
    public function confirmarContratacao(PlanoContratacao $plano, array $itens, ?int $usuarioId): void
    {
        DB::connection('egap')->transaction(function () use ($plano, $itens, $usuarioId): void {
            foreach ($itens as $item) {
                BemServico::query()
                    ->where('id_planocontratacao', $plano->id)
                    ->whereKey($item['id'])
                    ->update([
                        'data_executada' => $item['data_executada'] ?? null,
                        'num_processo_sei' => $item['num_processo_sei'] ?? null,
                        'id_etapa' => SituacaoPlano::Validado->value,
                    ]);
            }

            HistoricoPlano::registrar($plano->id, SituacaoPlano::Validado->value, $usuarioId);
        });
    }

    public function apagar(PlanoContratacao $plano): void
    {
        DB::connection('egap')->transaction(function () use ($plano): void {
            BemServico::query()->where('id_planocontratacao', $plano->id)->delete();
            HistoricoPlano::query()->where('id_planocontratacao', $plano->id)->delete();
            $plano->delete();
        });
    }

    /**
     * Aprova o plano (legado: `modificacao-solicitacao` com `deferido-todos != N`).
     */
    public function aprovar(PlanoContratacao $plano, ?int $usuarioId): void
    {
        DB::connection('egap')->transaction(function () use ($plano, $usuarioId): void {
            $etapa = SituacaoPlano::Concluido->value;

            $plano->update(['id_etapa' => $etapa]);
            BemServico::query()->where('id_planocontratacao', $plano->id)->update(['id_etapa' => $etapa]);

            HistoricoPlano::registrar($plano->id, $etapa, $usuarioId ?? $plano->id_solicitante);
        });
    }

    /**
     * Devolve o plano ao solicitante com comentário (legado:
     * `modificacao-solicitacao` com `deferido-todos == N`).
     */
    public function devolver(PlanoContratacao $plano, string $comentario, ?int $usuarioId): void
    {
        DB::connection('egap')->transaction(function () use ($plano, $comentario, $usuarioId): void {
            $etapa = SituacaoPlano::Devolvido->value;

            $plano->update(['id_etapa' => $etapa, 'comentario_adm' => $comentario]);

            BemServico::query()
                ->where('id_planocontratacao', $plano->id)
                ->where('id_etapa', '!=', SituacaoPlano::Concluido->value)
                ->update(['id_etapa' => $etapa]);

            HistoricoPlano::registrar($plano->id, $etapa, $usuarioId ?? $plano->id_solicitante);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function somaItens(array $itens): float
    {
        return collect($itens)->sum(fn (array $item): float => (float) ($item['valor1'] ?? 0) + (float) ($item['valor2'] ?? 0));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizarItem(array $item): array
    {
        $dados = array_intersect_key($item, array_flip($this->colunasItem()));

        foreach (['valor1', 'valor2', 'valor_global', 'valor_desembolsado_ano_pca'] as $numerico) {
            if (array_key_exists($numerico, $dados)) {
                $dados[$numerico] = $dados[$numerico] === null || $dados[$numerico] === ''
                    ? null
                    : (float) $dados[$numerico];
            }
        }

        if (array_key_exists('quantidade', $dados)) {
            $dados['quantidade'] = $dados['quantidade'] === null || $dados['quantidade'] === ''
                ? null
                : (int) $dados['quantidade'];
        }

        return $dados;
    }

    /**
     * @return array<int, string>
     */
    private function colunasItem(): array
    {
        return $this->colunasItem ??= array_values(array_filter(
            self::CAMPOS_ITEM,
            fn (string $coluna): bool => Schema::connection('egap')->hasColumn('pla_bensservicos', $coluna),
        ));
    }
}
