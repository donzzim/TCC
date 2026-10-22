<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Support\Collection;

/**
 * Relatorio "Bens Patrimoniais" (Bens Moveis).
 *
 * Legado: relatorios-prints-queries/bens-moveis/bens-patrimoniais.php  (3 queries)
 *
 * Query 1 (lista): mat_patrimonio LEFT marca/modelo, INNER setores/fornecedor,
 *   filtros: numero_processo, NotaFiscal, grupo (A => NOT IN B,C,D,E),
 *   acuracia, situacao_contabil. ORDER BY se.Setor, p.NumPatrimonio.
 * Query 2 (quadro "Bens de TI"): contagem/valor por grupo (A/B/C/D) por
 *   DescricaoResumida, WITH ROLLUP. -> quadroBensTi()
 * Query 3 (grafico): COUNT(*) por grupo dos "Bens de TI". -> graficoBensTi()
 */
final class BensPatrimoniaisService implements RelatorioService
{
    private const ROTULO_GRUPO = [
        'A' => 'Inventariado a partir de 2015',
        'B' => 'Inventariado antes de 2015',
        'C' => 'Inventário Online',
        'D' => 'A inventariar',
        'E' => 'Baixados',
    ];

    /**
     * @return Collection<int, array{
     *     num_patrimonio: mixed, descricao: ?string, marca: ?string, modelo: ?string,
     *     andar_setor: ?string, setor: ?string, numero_processo: ?string, nota_fiscal: ?string,
     *     valor_aquisicao: float, data_incorporacao: ?string, fornecedor: ?string, grupo: string
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        return BemMovel::query()
            ->with([
                'marcaRef:id,descricao',
                'modeloRef:id,descricao',
                'setorRef:id,setor',
                'fornecedorRef:id,NomeFornecedor',
            ])
            // INNER JOIN mat_setores / mat_fornecedor no legado.
            ->whereHas('setorRef')
            ->whereHas('fornecedorRef')
            ->when($filtros->numeroProcesso, fn ($q) => $q->where('numero_processo', $filtros->numeroProcesso))
            ->when($filtros->notaFiscal, fn ($q) => $q->where('NotaFiscal', $filtros->notaFiscal))
            ->when($filtros->grupo && $filtros->grupo !== '0', function ($q) use ($filtros) {
                $filtros->grupo === 'A'
                    ? $q->whereNotIn('grupo', ['B', 'C', 'D', 'E'])
                    : $q->where('grupo', $filtros->grupo);
            })
            ->when($filtros->acuracia, fn ($q) => $q->where('acuracia', $filtros->acuracia))
            ->when($filtros->situacaoContabil, fn ($q) => $q->where('situacao_contabil', $filtros->situacaoContabil))
            ->get([
                'id', 'NumPatrimonio', 'Descricao', 'Marca', 'Modelo', 'AndarSetor', 'Setor',
                'numero_processo', 'NotaFiscal', 'ValorAquisicao', 'DatadeIncorporacao', 'Fornecedor', 'grupo',
            ])
            ->map(fn (BemMovel $bem): array => [
                'num_patrimonio' => $bem->NumPatrimonio,
                'descricao' => $bem->Descricao,
                'marca' => $bem->marcaRef?->descricao,
                'modelo' => $bem->modeloRef?->descricao,
                'andar_setor' => $bem->AndarSetor,
                'setor' => $bem->setorRef?->setor,
                'numero_processo' => $bem->numero_processo,
                'nota_fiscal' => $bem->NotaFiscal,
                'valor_aquisicao' => (float) $bem->ValorAquisicao,
                'data_incorporacao' => $bem->DatadeIncorporacao?->format('d/m/Y'),
                'fornecedor' => $bem->fornecedorRef?->NomeFornecedor,
                'grupo' => self::ROTULO_GRUPO[$bem->grupo] ?? self::ROTULO_GRUPO['A'],
            ])
            // Legado: ORDER BY se.Setor, p.NumPatrimonio
            ->sort(fn ($a, $b) => [(string) $a['setor'], (string) $a['num_patrimonio']]
                <=> [(string) $b['setor'], (string) $b['num_patrimonio']])
            ->values();
    }

    /**
     * Query 2 do legado: por DescricaoResumida, contagem e valor de aquisicao dos
     * grupos A/B/C/D dos "Bens de TI", com linha final de TOTAL (ROLLUP).
     *
     * @return list<array<string, mixed>>
     */
    public function quadroBensTi(): array
    {
        $bens = BemMovel::query()
            ->where('acuracia', 'Bens de TI')
            ->with('descricaoResumidaBemRef:id,Descricao')
            ->get(['grupo', 'ValorAquisicao', 'DescricaoResumidadoBem']);

        $linha = static function (?string $descricao, Collection $doGrupo): array {
            $contaGrupo = fn (string $g) => $doGrupo->where('grupo', $g)->count();
            $somaGrupo = fn (string $g) => (float) $doGrupo->where('grupo', $g)->sum('ValorAquisicao');

            return [
                'descricao' => $descricao ?? 'TOTAL',
                'inventariado_a_partir_2015' => $contaGrupo('A'),
                'valor_a' => $somaGrupo('A'),
                'inventariado_antes_2015' => $contaGrupo('B'),
                'valor_b' => $somaGrupo('B'),
                'inventario_online' => $contaGrupo('C'),
                'valor_c' => $somaGrupo('C'),
                'a_inventariar' => $contaGrupo('D'),
                'valor_d' => $somaGrupo('D'),
                'total' => $doGrupo->count(),
            ];
        };

        $linhas = $bens
            ->groupBy(fn (BemMovel $bem) => $bem->descricaoResumidaBemRef?->Descricao)
            ->map(fn (Collection $doGrupo, $descricao) => $linha($descricao !== '' ? $descricao : null, $doGrupo))
            ->values()
            ->all();

        // ROLLUP: linha final TOTAL sobre todos os bens.
        $linhas[] = $linha(null, $bens);

        return $linhas;
    }

    /**
     * Query 3 do legado: COUNT(*) por grupo dos "Bens de TI".
     *
     * @return list<array{grupo: ?string, total: int}>
     */
    public function graficoBensTi(): array
    {
        return BemMovel::query()
            ->where('acuracia', 'Bens de TI')
            ->groupBy('grupo')
            ->select('grupo')
            ->selectRaw('COUNT(*) as total')
            ->get()
            ->map(fn ($r): array => [
                'grupo' => self::ROTULO_GRUPO[$r->grupo] ?? null,
                'total' => (int) $r->total,
            ])
            ->all();
    }
}
