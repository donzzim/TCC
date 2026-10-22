<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ConsultaPatrimonioContaProduto;
use Illuminate\Support\Collection;

/**
 * TCE - Tabela 11: Demonstrativo Analitico das Entradas e Saidas de Bens Moveis.
 *
 * Legado: relatorios-prints-queries/tce/tabela-11.php
 *
 * Base `pat` (conta x produto) + LEFT:
 *   - tab1   EntradaIncorporada : SUM(valor hist./reav.) FormaAquisicao = 'Compra', incorporado no periodo
 *   - tab2   EntradaDoacao      : idem, FormaAquisicao = 'Doacao'
 *   - concil EntradaOutros      : SUM(ValordaReavaliacao) das reavaliacoes do periodo
 *   - tab3   SaidaDoacao        : SUM(valor hist./reav.) DataBaixa no periodo, SituacaoBem = 3
 *   - tab4   SaidaPerda         : idem, SituacaoBem = 5
 *   - tab5   SaidaOutro         : idem, SituacaoBem IN (2,4,6)
 * Totais de entrada/saida somados na projecao.
 */
final class Tabela11Service implements RelatorioService
{
    use ConsultaPatrimonioContaProduto;

    private const CORTE = '2015-01-01 00:00';

    /**
     * @return Collection<int, array{
     *     conta_contabil: ?string, produto: ?string, descricao: ?string,
     *     entrada_incorporada: float, entrada_doacao: float, entrada_outros: float, total_entrada: float,
     *     saida_doacao: float, saida_perda: float, saida_outra: float, total_saida: float
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioHora();
        $fim = $filtros->fimHora();
        $expr = $this->somaValorHistorico(self::CORTE);

        $base = $this->tuplasPatrimonioContaProduto($filtros);
        if ($base->isEmpty()) {
            return collect();
        }

        $tab1 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DatadeIncorporacao', [$inicio, $fim])->whereNotIn('SituacaoBem', [8, 9])->where('FormaAquisicao', 'Compra');
        });

        $tab2 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DatadeIncorporacao', [$inicio, $fim])->whereNotIn('SituacaoBem', [8, 9])->where('FormaAquisicao', 'Doação');
        });

        $concil = $this->somaPatrimonioPorContaProduto($filtros, 'SUM(ValordaReavaliacao)', function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DatadaReavaliacao', [$inicio, $fim])->whereNotIn('SituacaoBem', [8, 9]);
        });

        $tab3 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DataBaixa', [$inicio, $fim])->where('SituacaoBem', 3);
        });

        $tab4 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DataBaixa', [$inicio, $fim])->where('SituacaoBem', 5);
        });

        $tab5 = $this->somaPatrimonioPorContaProduto($filtros, $expr, function ($q) use ($inicio, $fim): void {
            $q->whereBetween('DataBaixa', [$inicio, $fim])->whereIn('SituacaoBem', [2, 4, 6]);
        });

        return $base->map(function ($t) use ($tab1, $tab2, $concil, $tab3, $tab4, $tab5): array {
            $chave = $t->id_conta.'|'.$t->id_produto;
            $get = fn (Collection $c) => (float) ($c[$chave]->valor ?? 0);

            $entradaIncorporada = $get($tab1);
            $entradaDoacao = $get($tab2);
            $entradaOutros = $get($concil);
            $saidaDoacao = $get($tab3);
            $saidaPerda = $get($tab4);
            $saidaOutra = $get($tab5);

            return [
                'conta_contabil' => $t->conta_contabil,
                'produto' => $t->produto,
                'descricao' => $t->descricao,
                'entrada_incorporada' => $entradaIncorporada,
                'entrada_doacao' => $entradaDoacao,
                'entrada_outros' => $entradaOutros,
                'total_entrada' => $entradaIncorporada + $entradaDoacao + $entradaOutros,
                'saida_doacao' => $saidaDoacao,
                'saida_perda' => $saidaPerda,
                'saida_outra' => $saidaOutra,
                'total_saida' => $saidaDoacao + $saidaPerda + $saidaOutra,
            ];
        })->values();
    }
}
