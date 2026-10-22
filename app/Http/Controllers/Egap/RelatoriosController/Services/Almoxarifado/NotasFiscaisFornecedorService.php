<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Models\egap\Almoxarifado\NotaFiscal;
use Illuminate\Support\Collection;

/**
 * Relatorio "Notas Fiscais por Fornecedor".
 *
 * Legado: relatorios-prints-queries/almoxarifado/notas-fiscais-por-fornecedor.php
 *
 * Query 1 (NFs):  alm_notafiscal LEFT mat_fornecedor, periodo = MES do termino
 *                 ate o dia do termino, ORDER BY fnd.CNPJ, nf.num_documento.
 * Query 2 (itens, no legado em loop por NF): alm_itens_notafiscal LEFT dd/dr/el.
 *
 * Aqui: uma consulta Eloquent com eager loading de itens (material ->
 * descricao_resumida -> produto). Ordena por CNPJ/num_documento em PHP.
 * Devolve as NFs com os itens embutidos; o "quadro resumo" (qtde NFs, qtde
 * itens, soma dos totais) e trivial e fica na apresentacao.
 */
final class NotasFiscaisFornecedorService implements RelatorioService
{
    /**
     * @return Collection<int, array{
     *     id: int, num_documento: ?string, data_documento: ?string, tipo_documento: string,
     *     fornecedor_id: ?int, fornecedor: ?string, cnpj: string,
     *     itens: list<array{
     *         quantidade: int, preco_unitario: float, valor_total: float,
     *         descricao_detalhada: ?string, descricao_resumida: ?string,
     *         elemento_codigo: ?string, elemento_descricao: ?string
     *     }>
     * }>
     */
    public function gerar(FiltrosRelatorio $filtros): Collection
    {
        $inicio = $filtros->inicioMesDoTermino(); // "Y-m-01" (mes do termino)
        $termino = $filtros->fimHora();           // "Y-m-d 23:59:59"

        return NotaFiscal::query()
            ->whereBetween('date_time', [$inicio, $termino])
            ->with([
                'fornecedorRef:id,NomeFornecedor,CNPJ',
                'itens.material:id,descricao_detalhada,descricao_resumida',
                'itens.material.descricao_resumida_text:id,Descricao,id_produto',
                'itens.material.descricao_resumida_text.produto_id:id,CodigodaClasse,DescricaodaClasse',
            ])
            ->get()
            ->map(fn (NotaFiscal $nf): array => [
                'id' => (int) $nf->id,
                'num_documento' => $nf->num_documento,
                'data_documento' => $nf->data_documento?->format('d/m/Y'),
                // Legado: tipo_documento = 1 => "Nota Fiscal"; senao "Nota Fiscal/Doacao".
                'tipo_documento' => (int) $nf->tipo_documento === 1 ? 'Nota Fiscal' : 'Nota Fiscal/Doação',
                'fornecedor_id' => $nf->fornecedor !== null ? (int) $nf->fornecedor : null,
                'fornecedor' => $nf->fornecedorRef?->NomeFornecedor,
                'cnpj' => (string) ($nf->fornecedorRef?->CNPJ ?? '0'),
                'itens' => $nf->itens->map(function ($item): array {
                    $resumida = $item->material?->descricao_resumida_text;

                    return [
                        // Legado: ROUND(inf.quantidade, 0)
                        'quantidade' => (int) round((float) $item->quantidade),
                        'preco_unitario' => (float) $item->preco_unitario,
                        'valor_total' => (float) $item->total_item,
                        'descricao_detalhada' => $item->material?->descricao_detalhada,
                        'descricao_resumida' => $resumida?->Descricao,
                        'elemento_codigo' => $resumida?->produto_id?->CodigodaClasse,
                        'elemento_descricao' => $resumida?->produto_id?->DescricaodaClasse,
                    ];
                })->all(),
            ])
            // Legado: ORDER BY fnd.CNPJ, nf.num_documento
            ->sort(fn ($a, $b) => [$a['cnpj'], (string) $a['num_documento']] <=> [$b['cnpj'], (string) $b['num_documento']])
            ->values();
    }
}
