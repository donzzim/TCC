<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatorio "Bens Incorporados".
 *
 * Legado: relatorios-prints-queries/bens-moveis/bens-incorporados.php
 *
 *   SELECT pc.codigo, p.NumPatrimonio, dr.Descricao, ma.descricao Marca, mo.descricao Modelo,
 *          p.DatadeIncorporacao,
 *          IF(DatadeIncorporacao < '2015-01-01 00:00', ValordaReavaliacao, ValorAquisicao) Valor,
 *          p.FormaAquisicao, sit.descricao, se.Setor
 *   FROM mat_patrimonio p
 *     LEFT JOIN mat_descricaoresumida dr / mat_planocontas pc
 *     INNER JOIN mat_situacao sit
 *     LEFT JOIN mat_setores se / mat_marca ma / mat_modelo mo
 *   WHERE (p.DatadeIncorporacao BETWEEN :inicio AND :termino AND p.SituacaoBem NOT IN (8,9))
 *     {AND p.ContaContabil = :conta} {AND p.unidade_gestora = :unidade} {AND p.situacao_contabil = :situacao}
 *   ORDER BY pc.codigo, p.NumPatrimonio
 *
 * `Valor` (corte historico 2015) e derivado em PHP. Agrupamento por conta e
 * subtotais ficam na apresentacao.
 */
final class BensIncorporadosService implements RelatorioService
{
    private const CORTE_HISTORICO = '2015-01-01 00:00:00';

    /**
     * @return Collection<int, array{
     *     conta_codigo: ?string, num_patrimonio: mixed, descricao: ?string,
     *     marca: ?string, modelo: ?string, data_incorporacao: ?string, valor: float,
     *     forma_aquisicao: ?string, situacao: ?string, setor: ?string
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        // BUG-LEGADO: BETWEEN com valores crus; termino nao vai ate o fim do dia.
        $inicio = $filtros->inicioObrigatorio()->format('Y-m-d 00:00:00');
        $termino = $filtros->terminoObrigatorio()->format('Y-m-d 00:00:00');

        return BemMovel::query()
            ->with([
                'descricaoResumidaBemRef:id,Descricao',
                'contaContabilRef:id,codigo',
                'situacaoBemRef:id,descricao',
                'setorRef:id,setor',
                'marcaRef:id,descricao',
                'modeloRef:id,descricao',
            ])
            ->whereBetween('DatadeIncorporacao', [$inicio, $termino])
            ->whereNotIn('SituacaoBem', [8, 9])
            ->when($filtros->conta, fn ($q) => $q->where('ContaContabil', $filtros->conta))
            // Legado tinha filtro de unidade_gestora, mas nunca era enviado por
            // este relatorio (campo sempre oculto no JS). Omitido.
            ->when($filtros->situacaoContabil, fn ($q) => $q->where('situacao_contabil', $filtros->situacaoContabil))
            ->get([
                'id', 'ContaContabil', 'NumPatrimonio', 'DescricaoResumidadoBem', 'Marca', 'Modelo',
                'Setor', 'SituacaoBem', 'DatadeIncorporacao', 'ValordaReavaliacao', 'ValorAquisicao',
                'FormaAquisicao',
            ])
            ->map(fn (BemMovel $bem): array => [
                'conta_codigo' => $bem->contaContabilRef?->codigo,
                'num_patrimonio' => $bem->NumPatrimonio,
                'descricao' => $bem->descricaoResumidaBemRef?->Descricao,
                'marca' => $bem->marcaRef?->descricao,
                'modelo' => $bem->modeloRef?->descricao,
                'data_incorporacao' => $bem->DatadeIncorporacao?->format('d/m/Y'),
                'valor' => $this->valorHistorico($bem),
                'forma_aquisicao' => $bem->FormaAquisicao,
                'situacao' => $bem->situacaoBemRef?->descricao,
                'setor' => $bem->setorRef?->setor,
            ])
            // Legado: ORDER BY pc.codigo, p.NumPatrimonio
            ->sort(fn ($a, $b) => [(string) $a['conta_codigo'], (string) $a['num_patrimonio']]
                <=> [(string) $b['conta_codigo'], (string) $b['num_patrimonio']])
            ->values();
    }

    private function valorHistorico(BemMovel $bem): float
    {
        $incorporacao = $bem->DatadeIncorporacao;

        if ($incorporacao !== null && $incorporacao->lt(Carbon::parse(self::CORTE_HISTORICO))) {
            return (float) $bem->ValordaReavaliacao;
        }

        return (float) $bem->ValorAquisicao;
    }
}
