<?php

namespace Tests\Feature;

use App\Http\Controllers\Egap\RelatoriosController\Data\FiltrosRelatorio;
use App\Http\Controllers\Egap\RelatoriosController\Services\Almoxarifado\ResumoInventarioAlmoxarifadoCcService;
use App\Http\Controllers\Egap\RelatoriosController\Services\BensImoveis\DepreciacaoMensalImoveisCcService;
use App\Services\Egap\CentroCustoSetor;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guarda dos relatórios por centro de custo.
 *
 * O centro de custo saiu de `egap_cad_centrocusto` (código pontuado,
 * `3.1.05.1.00.00.000`) para `setores.centrocusto` (14 dígitos hierárquicos,
 * `31005000000000`). Os dois domínios não se cruzam, e a consulta antiga
 * (`where('centrocusto', $centro->codigo)`) devolvia array vazio: o service
 * retornava `collect()` e o relatório saía em branco, sem erro e sem log.
 *
 * Estes testes falham se a resolução voltar a não encontrar setores, se o
 * agrupamento mudar de granularidade, ou se o join de rótulo passar a
 * multiplicar linhas.
 */
class EgapCentroCustoSetorTest extends TestCase
{
    /** Granularidade esperada: ~79 centros, na ordem de grandeza dos 84 antigos. */
    private const MINIMO_CENTROS = 70;

    public function test_it_lists_cost_centers_without_the_legacy_table(): void
    {
        $centros = CentroCustoSetor::listar();

        $this->assertGreaterThan(self::MINIMO_CENTROS, $centros->count());

        foreach ($centros as $centro) {
            $this->assertMatchesRegularExpression('/^\d{5}$/', $centro->codigo);
            $this->assertNotNull($centro->descricao, "centro {$centro->codigo} sem rótulo");
        }
    }

    public function test_every_cost_center_resolves_to_sectors(): void
    {
        $semSetor = CentroCustoSetor::listar()
            ->filter(fn ($c): bool => CentroCustoSetor::setorIds($c->codigo) === [])
            ->pluck('codigo')
            ->all();

        $this->assertSame([], $semSetor, 'centro de custo sem nenhum setor: '.implode(', ', $semSetor));
    }

    /**
     * O rótulo do centro de custo raiz é a unidade organizacional, não um
     * setor filho que por acaso carregue o mesmo código. Sem o desempate por
     * `id = codigopai`, o 31000 vinha como "LABORATORIO DE INOVACAO...".
     */
    public function test_the_root_label_prefers_the_organizational_unit(): void
    {
        $opcoes = CentroCustoSetor::opcoesFiltro();

        $this->assertArrayHasKey('31000', $opcoes);
        $this->assertStringContainsString('TRIBUNAL DE JUSTIÇA', $opcoes['31000']);
        $this->assertStringContainsString('PRESIDÊNCIA', $opcoes['31005']);
    }

    public function test_the_filter_options_are_code_and_label(): void
    {
        $opcoes = CentroCustoSetor::opcoesFiltro();

        $this->assertGreaterThan(self::MINIMO_CENTROS, count($opcoes));

        $codigo = array_key_first($opcoes);
        $this->assertMatchesRegularExpression('/^\d{5}$/', (string) $codigo);
        $this->assertStringStartsWith((string) $codigo, $opcoes[$codigo]);
    }

    /**
     * O rótulo vem de uma subquery agrupada justamente porque há prefixo com
     * mais de uma raiz (o 33043 tem duas). Um join ingênuo multiplicaria as
     * linhas do relatório — e inflaria todos os totais.
     */
    public function test_the_label_join_does_not_multiply_rows(): void
    {
        $sem = DB::connection('emes')
            ->table('egap_mat_patrimonio as pat')
            ->join('setores as s', 'pat.Setor', '=', 's.id')
            ->count();

        $com = DB::connection('emes')
            ->table('egap_mat_patrimonio as pat')
            ->join('setores as s', 'pat.Setor', '=', 's.id')
            ->tap(fn ($q) => CentroCustoSetor::aplicarJoins($q, 's'))
            ->count();

        $this->assertSame($sem, $com, 'a resolução de centro de custo inflou o número de linhas');
    }

    public function test_the_hierarchy_covers_sectors_without_their_own_cost_center(): void
    {
        $query = DB::connection('emes')->table('setores as s');
        CentroCustoSetor::aplicarJoins($query, 's', comRotulo: false);

        $resolvidos = $query->whereRaw(CentroCustoSetor::centroCustoSql('s').' IS NOT NULL')->count();
        $proprios = DB::connection('emes')->table('setores')->whereNotNull('centrocusto')->count();

        $this->assertGreaterThan(
            $proprios,
            $resolvidos,
            'a subida na hierarquia tem de resolver mais setores do que os que têm centro de custo próprio'
        );
    }

    public function test_the_almoxarifado_report_returns_data(): void
    {
        $linhas = (new ResumoInventarioAlmoxarifadoCcService)->gerar($this->filtros());

        $this->assertGreaterThan(0, $linhas->count(), 'relatório vazio — provável regressão do centro de custo');
        $this->assertArrayHasKey('centro_custo_codigo', $linhas->first());
        $this->assertArrayHasKey('centro_custo_descricao', $linhas->first());
    }

    public function test_the_imoveis_report_returns_data(): void
    {
        $linhas = (new DepreciacaoMensalImoveisCcService)->gerar($this->filtros());

        $this->assertGreaterThan(0, $linhas->count(), 'relatório vazio — provável regressão do centro de custo');
        $this->assertArrayHasKey('centro_custo_codigo', $linhas->first());
    }

    private function filtros(): FiltrosRelatorio
    {
        return FiltrosRelatorio::fromArray([
            'data_inicio' => '2025-01-01',
            'data_termino' => '2025-12-31',
        ]);
    }
}
