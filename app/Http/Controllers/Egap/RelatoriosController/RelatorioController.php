<?php

namespace App\Http\Controllers\Egap\RelatoriosController;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\BalanceteContabilAnaliticoService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\ConsumoMaterialSubelementoService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\GastoAnualItensEstoqueService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\MediaConsumoMaterialComarcaService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\MediaConsumoMaterialService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\NotasFiscaisFornecedorService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\PedidosValidadosSetorService;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\ResumoInventarioAlmoxarifadoCcService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\AjustesReavaliacaoImoveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\DepreciacaoMensalImoveisCcService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\DepreciacaoMensalImoveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\InventarioBensImoveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\RelacaoBensImoveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\SaldoAnteriorImoveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensIntangiveis\InventarioBensIntangiveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\AnaliticoContabilService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\BensBaixadosPorProcessoService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\BensBaixadosService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\BensConciliadosService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\BensIncorporadosService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\BensPatrimoniaisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\DepreciacaoMensalService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\InventarioBensMoveisDetalhadoService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensMoveis\InventarioBensMoveisService;
use App\Http\Controllers\Egap\RelatoriosController\Services\RelatorioService;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela10Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela11Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela12Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela13Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela14Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela15Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela16Service;
use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Tabela17Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Controller dos "Relatorios Gerais".
 *
 * Recebe o slug do relatorio + filtros (GET), resolve o Service correspondente,
 * executa `gerar(FiltrosRelatorio)` e entrega o dataset para a view Blade
 * `egap.relatorios.{dominio}.{slug}` (mesmo padrao dinamico do legado
 * ReportRelatoriosGeraisController::render()).
 *
 * O payload padrao para toda view e:
 *   - relatorio     : string  slug do relatorio
 *   - titulo        : string  rotulo legivel
 *   - filtros       : FiltrosRelatorio
 *   - dados         : Collection<int, array>  linhas do relatorio
 *   - data_emissao  : string  d/m/Y
 *   - formato       : 'html' | 'excel'
 * Alguns relatorios acrescentam blocos extras (ver blocosExtras()).
 */
class RelatorioController extends Controller
{
    /**
     * Dominios (grupos) dos relatorios: slug-de-pasta => rotulo legivel.
     * O slug tambem e o subdiretorio da view: `egap.relatorios.{dominio}.{slug}`.
     *
     * @var array<string, string>
     */
    private const DOMINIOS = [
        'almoxarifado' => 'Almoxarifado',
        'bens-intangiveis' => 'Bens Intangíveis',
        'bens-moveis' => 'Bens Móveis',
        'bens-imoveis' => 'Bens Imóveis',
        'tce' => 'TCE',
    ];

    /**
     * Registro dos relatorios: slug => [Service, rotulo, dominio].
     *
     * O slug resolve o Service; o par (dominio, slug) resolve a view
     * `egap.relatorios.{dominio}.{slug}`.
     *
     * @var array<string, array{0: class-string<RelatorioService>, 1: string, 2: string}>
     */
    private const RELATORIOS = [
        // --- Almoxarifado ---
        'gasto-anual-itens-estoque' => [GastoAnualItensEstoqueService::class, 'Gasto Anual com Itens de Estoque', 'almoxarifado'],
        'media-consumo-material' => [MediaConsumoMaterialService::class, 'Média de Consumo por Material', 'almoxarifado'],
        'media-consumo-material-comarca' => [MediaConsumoMaterialComarcaService::class, 'Média de Consumo por Material e Setor', 'almoxarifado'],
        'consumo-material-subelemento' => [ConsumoMaterialSubelementoService::class, 'Consumo de Material por Subelemento de Despesa', 'almoxarifado'],
        'notas-fiscais-fornecedor' => [NotasFiscaisFornecedorService::class, 'Notas Fiscais por Fornecedor', 'almoxarifado'],
        'pedidos-validados-setor' => [PedidosValidadosSetorService::class, 'Pedidos Validados pelo Setor', 'almoxarifado'],
        'balancete-contabil-analitico' => [BalanceteContabilAnaliticoService::class, 'Balancete Contábil Analítico', 'almoxarifado'],
        'resumo-inventario-almoxarifado-cc' => [ResumoInventarioAlmoxarifadoCcService::class, 'Resumo do Inventário do Almoxarifado por Centro de Custo', 'almoxarifado'],

        // --- Bens Intangiveis ---
        'inventario-bens-intangiveis' => [InventarioBensIntangiveisService::class, 'Inventário dos Bens Intangíveis', 'bens-intangiveis'],

        // --- Bens Moveis ---
        'bens-patrimoniais' => [BensPatrimoniaisService::class, 'Bens Patrimoniais', 'bens-moveis'],
        'analitico-contabil' => [AnaliticoContabilService::class, 'Analítico Contábil', 'bens-moveis'],
        'bens-incorporados' => [BensIncorporadosService::class, 'Bens Incorporados', 'bens-moveis'],
        'bens-baixados' => [BensBaixadosService::class, 'Bens Baixados', 'bens-moveis'],
        'bens-baixados-por-processo' => [BensBaixadosPorProcessoService::class, 'Bens Baixados por Processo', 'bens-moveis'],
        'bens-conciliados' => [BensConciliadosService::class, 'Bens Conciliados', 'bens-moveis'],
        'depreciacao-mensal' => [DepreciacaoMensalService::class, 'Depreciação Mensal', 'bens-moveis'],
        'inventario-bens-moveis' => [InventarioBensMoveisService::class, 'Inventário dos Bens Móveis', 'bens-moveis'],
        'inventario-bens-moveis-detalhado' => [InventarioBensMoveisDetalhadoService::class, 'Inventário dos Bens Móveis - Detalhado', 'bens-moveis'],

        // --- Bens Imoveis ---
        'relacao-bens-imoveis' => [RelacaoBensImoveisService::class, 'Relação dos Bens Imóveis', 'bens-imoveis'],
        'inventario-bens-imoveis' => [InventarioBensImoveisService::class, 'Inventário dos Bens Imóveis', 'bens-imoveis'],
        'ajustes-reavaliacao-imoveis' => [AjustesReavaliacaoImoveisService::class, 'Ajustes após Reavaliação dos Imóveis', 'bens-imoveis'],
        'depreciacao-mensal-imoveis' => [DepreciacaoMensalImoveisService::class, 'Depreciação Mensal Imóveis', 'bens-imoveis'],
        'depreciacao-mensal-imoveis-cc' => [DepreciacaoMensalImoveisCcService::class, 'Depreciação Mensal Imóveis por Centro de Custo', 'bens-imoveis'],
        'saldo-anterior-imoveis' => [SaldoAnteriorImoveisService::class, 'Saldo Anterior de Imóveis', 'bens-imoveis'],

        // --- TCE (IN 34) ---
        'tce-tabela-10' => [Tabela10Service::class, 'TCE - Tabela 10 - Resumo do Inventário dos Bens Móveis', 'tce'],
        'tce-tabela-11' => [Tabela11Service::class, 'TCE - Tabela 11 - Analítico das Entradas e Saídas de Bens Móveis', 'tce'],
        'tce-tabela-12' => [Tabela12Service::class, 'TCE - Tabela 12 - Resumo do Inventário dos Bens Imóveis', 'tce'],
        'tce-tabela-13' => [Tabela13Service::class, 'TCE - Tabela 13 - Analítico das Entradas e Saídas de Bens Imóveis', 'tce'],
        'tce-tabela-14' => [Tabela14Service::class, 'TCE - Tabela 14 - Resumo do Inventário do Almoxarifado (Consumo)', 'tce'],
        'tce-tabela-15' => [Tabela15Service::class, 'TCE - Tabela 15 - Analítico do Almoxarifado (Consumo)', 'tce'],
        'tce-tabela-16' => [Tabela16Service::class, 'TCE - Tabela 16 - Resumo do Inventário do Almoxarifado (Permanente)', 'tce'],
        'tce-tabela-17' => [Tabela17Service::class, 'TCE - Tabela 17 - Analítico do Almoxarifado (Permanente)', 'tce'],
    ];

    /**
     * Filtros que cada relatorio realmente consome (o Service ignora o resto).
     * Melhoria sobre o legado: no antigo JS varios campos apareciam sem efeito.
     *
     * Tokens: periodo | conta | conta_req | situacao | centro_custo
     *         | processo | nota_fiscal | grupo | acuracia | materiais
     *
     * @var array<string, string>
     */
    private const FILTROS = [
        // Almoxarifado
        'gasto-anual-itens-estoque' => 'periodo',
        'media-consumo-material' => 'periodo,materiais',
        'media-consumo-material-comarca' => 'periodo,materiais',
        'consumo-material-subelemento' => 'periodo,materiais',
        'notas-fiscais-fornecedor' => 'periodo',
        'pedidos-validados-setor' => 'periodo',
        'balancete-contabil-analitico' => 'periodo',
        'resumo-inventario-almoxarifado-cc' => 'periodo,centro_custo',
        // Bens Intangiveis
        'inventario-bens-intangiveis' => '',
        // Bens Moveis
        'bens-patrimoniais' => 'processo,nota_fiscal,grupo,acuracia,situacao',
        'analitico-contabil' => 'periodo,conta,situacao',
        'bens-incorporados' => 'periodo,conta,situacao',
        'bens-baixados' => 'periodo,conta,situacao',
        'bens-baixados-por-processo' => 'periodo',
        'bens-conciliados' => 'periodo',
        'depreciacao-mensal' => 'periodo,conta,situacao',
        'inventario-bens-moveis' => 'periodo,conta_req,situacao',
        'inventario-bens-moveis-detalhado' => 'periodo,conta,situacao',
        // Bens Imoveis
        'relacao-bens-imoveis' => 'periodo',
        'inventario-bens-imoveis' => 'periodo,conta',
        'ajustes-reavaliacao-imoveis' => 'periodo',
        'depreciacao-mensal-imoveis' => 'periodo',
        'depreciacao-mensal-imoveis-cc' => 'periodo,centro_custo',
        'saldo-anterior-imoveis' => 'periodo',
        // TCE
        'tce-tabela-10' => 'periodo,conta,situacao',
        'tce-tabela-11' => 'periodo,conta,situacao',
        'tce-tabela-12' => 'periodo,conta',
        'tce-tabela-13' => 'periodo,conta',
        'tce-tabela-14' => 'periodo',
        'tce-tabela-15' => 'periodo',
        'tce-tabela-16' => 'periodo',
        'tce-tabela-17' => 'periodo',
    ];

    /**
     * Gera o relatorio e devolve a view Blade correspondente.
     *
     * Espera na query string:
     *   - relatorio (obrigatorio) : slug do relatorio (chave de self::RELATORIOS)
     *   - tipo=excel (opcional)   : sinaliza exportacao (legado); renderer decide na view
     *   - demais filtros          : data_inicio, data_termino, conta_contabil, situacao_contabil,
     *                               centro_custo, numero_processo, nota_fiscal,
     *                               grupo, acuracia, materiais[]  (ver FiltrosRelatorio::fromArray)
     */
    public function imprimir(Request $request): View
    {
        $slug = trim((string) $request->input('relatorio', ''));

        abort_unless(
            $slug !== '' && array_key_exists($slug, self::RELATORIOS),
            404,
            "Relatório não encontrado: {$slug}",
        );

        [$serviceClass, $titulo, $dominio] = self::RELATORIOS[$slug];

        // Relatorios anuais chegam a ~100 mil linhas (inventario detalhado: pico de
        // ~370 MB entre dataset e HTML). Com o padrao de 128M a aba abria em erro.
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $filtros = FiltrosRelatorio::fromArray($request->all());

        /** @var RelatorioService $service */
        $service = app($serviceClass);
        $dados = $service->gerar($filtros);

        $payload = [
            'relatorio' => $slug,
            'titulo' => $titulo,
            'dominio' => self::DOMINIOS[$dominio],
            'filtros' => $filtros,
            'dados' => $dados,
            'data_emissao' => now()->format('d/m/Y'),
            'formato' => $request->input('tipo') === 'excel' ? 'excel' : 'html',
            ...$this->blocosExtras($service, $dados),
        ];

        return view("egap.relatorios.{$dominio}.{$slug}", $payload);
    }

    /**
     * Opcoes para o <select> de relatorios (pagina Filament), agrupadas por dominio.
     *
     * @return array<string, array<string, string>>  ['Grupo' => ['slug' => 'Rótulo']]
     */
    public static function opcoesAgrupadas(): array
    {
        $opcoes = [];

        foreach (self::RELATORIOS as $slug => [, $titulo, $dominio]) {
            $opcoes[self::DOMINIOS[$dominio]][$slug] = $titulo;
        }

        return $opcoes;
    }

    /**
     * @return array<string, string>  ['slug' => 'Rótulo']
     */
    public static function opcoes(): array
    {
        return array_map(static fn (array $config): string => $config[1], self::RELATORIOS);
    }

    /**
     * Tokens de filtro que o relatorio consome. Slug invalido => [].
     *
     * @return list<string>
     */
    public static function filtros(string $slug): array
    {
        return array_values(array_filter(explode(',', self::FILTROS[$slug] ?? '')));
    }

    /** O relatorio possui algum filtro configuravel? */
    public static function temFiltros(string $slug): bool
    {
        return self::filtros($slug) !== [];
    }

    /**
     * Blocos adicionais no payload da view, para os relatorios cujo Service
     * expoe mais do que o dataset principal (totalizacoes, quadros-resumo, etc.).
     *
     * @param  Collection<int, array<string, mixed>>  $dados
     * @return array<string, mixed>
     */
    private function blocosExtras(RelatorioService $service, Collection $dados): array
    {
        if ($service instanceof ResumoInventarioAlmoxarifadoCcService
            || $service instanceof DepreciacaoMensalImoveisCcService) {
            // detalhe (linhas) + 3 blocos de totalizacao (por CC / conta / item patrimonial)
            return ['resumos' => $service->resumos($dados)];
        }

        if ($service instanceof BensPatrimoniaisService) {
            return [
                'quadro_bens_ti' => $service->quadroBensTi(),
                'grafico_bens_ti' => $service->graficoBensTi(),
            ];
        }

        if ($service instanceof BensBaixadosService) {
            return ['resumo_depreciacao_saidas' => $service->resumoDepreciacaoSaidas($dados)];
        }

        return [];
    }
}
