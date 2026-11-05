<?php

namespace Tests\Feature\Monitoramento;

use App\Monitoramento\Agregacao\AgregadoMinuto;
use App\Monitoramento\Agregacao\Histograma;
use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use App\Monitoramento\Coleta\Snapshot;
use App\Monitoramento\Leitura\Periodo;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class LeituraDeMetricasTest extends TestCase
{
    use UsaBancoDeMetricas;

    private const AGORA = 1_700_002_400;

    private RepositorioDeMetricas $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usarBancoDeMetricas();
        $this->repo = app(RepositorioDeMetricas::class);
    }

    private function agregado(int $minuto, string $rota, int $qtd, float $ms, int $erros5xx = 0, string $contexto = 'egap', int $sqlQtd = 0, float $sqlMs = 0): AgregadoMinuto
    {
        $faixas = Histograma::vazio();
        $faixas[Histograma::faixaDe($ms)] = $qtd;

        return new AgregadoMinuto($minuto, $contexto, $rota, $qtd, 0, $erros5xx, $ms * $qtd, $ms, $sqlMs, $sqlQtd, 20.0, 0, $faixas);
    }

    private function minuto(int $atras): int
    {
        return intdiv(self::AGORA, 60) * 60 - $atras * 60;
    }

    public function test_resumo_do_periodo(): void
    {
        $this->repo->gravarAgregados([
            $this->agregado($this->minuto(2), 'a', 95, 80),
            $this->agregado($this->minuto(3), 'b', 5, 1800, erros5xx: 2),
            $this->agregado($this->minuto(120), 'a', 50, 80),
        ]);

        $r = $this->repo->resumo(Periodo::H1, null, self::AGORA);

        $this->assertSame(100, $r->qtd);
        $this->assertSame(2, $r->erros5xx);
        $this->assertSame(100.0, $r->p95);
        $this->assertSame(2000.0, $r->p99);
        $this->assertEqualsWithDelta(0.02, $r->taxaErro5xx, 0.0001);
        $this->assertEqualsWithDelta(100 / 60, $r->reqPorMin, 0.0001);
    }

    public function test_periodo_anterior_nao_inclui_o_atual(): void
    {
        $this->repo->gravarAgregados([
            $this->agregado($this->minuto(2), 'a', 10, 80),
            $this->agregado($this->minuto(70), 'a', 4, 80),
        ]);
        $this->repo->gravarDetalhadas([
            new RegistroDeRequisicao(self::AGORA - 60, 'egap', 'x', 'GET', 200, 1500, 1, 1, 1, [], false, 'atual', ['top_consultas' => [], 'erro' => null]),
        ]);
        $this->repo->gravarSnapshot(new Snapshot(self::AGORA - 60, null, null, null, null));

        $anterior = self::AGORA - Periodo::H1->segundos();

        $this->assertSame(4, $this->repo->resumo(Periodo::H1, null, $anterior)->qtd);
        $this->assertSame([], $this->repo->requisicoesLentas(Periodo::H1, null, 10, $anterior));
        $this->assertSame([], $this->repo->serieSnapshots(Periodo::H1, $anterior));
    }

    public function test_resumo_sem_dados(): void
    {
        $r = $this->repo->resumo(Periodo::Q15, null, self::AGORA);

        $this->assertSame(0, $r->qtd);
        $this->assertNull($r->p95);
        $this->assertSame(0.0, $r->taxaErro5xx);
    }

    public function test_filtro_de_contexto(): void
    {
        $this->repo->gravarAgregados([
            $this->agregado($this->minuto(2), 'a', 10, 80),
            $this->agregado($this->minuto(2), 'a', 7, 80, contexto: 'mobile-api'),
        ]);

        $this->assertSame(7, $this->repo->resumo(Periodo::H1, 'mobile-api', self::AGORA)->qtd);
        $this->assertSame(17, $this->repo->resumo(Periodo::H1, null, self::AGORA)->qtd);
    }

    public function test_serie_latencia_tem_passos_vazios_nulos(): void
    {
        $this->repo->gravarAgregados([$this->agregado($this->minuto(2), 'a', 10, 80)]);

        $serie = $this->repo->serieLatencia(Periodo::Q15, null, self::AGORA);

        $this->assertGreaterThanOrEqual(15, count($serie));
        $comDados = array_values(array_filter($serie, fn ($p) => $p->valores['p95'] !== null));
        $this->assertCount(1, $comDados);
        $this->assertSame($this->minuto(2), $comDados[0]->instante);
        $this->assertSame(100.0, $comDados[0]->valores['p95']);
    }

    public function test_serie_volume_por_contexto(): void
    {
        $this->repo->gravarAgregados([
            $this->agregado($this->minuto(2), 'a', 10, 80),
            $this->agregado($this->minuto(2), 'a', 4, 80, contexto: 'mobile-api'),
        ]);

        $ponto = collect($this->repo->serieVolume(Periodo::Q15, self::AGORA))->firstWhere('instante', $this->minuto(2));

        $this->assertSame(10.0, $ponto->valores['egap']);
        $this->assertSame(4.0, $ponto->valores['mobile-api']);
    }

    public function test_rotas_ordenadas(): void
    {
        $this->repo->gravarAgregados([
            $this->agregado($this->minuto(2), 'muitas-rapidas', 1000, 40, sqlQtd: 3000, sqlMs: 10000),
            $this->agregado($this->minuto(2), 'poucas-lentas', 3, 4000),
            $this->agregado($this->minuto(2), 'livewire:app.tabela', 50, 300),
        ]);

        $porTempo = $this->repo->rotas(Periodo::H1, null, 'tempo_total', 10, self::AGORA);
        $porP95 = $this->repo->rotas(Periodo::H1, null, 'p95', 10, self::AGORA);
        $livewire = $this->repo->rotas(Periodo::H1, null, 'tempo_total', 10, self::AGORA, somenteLivewire: true);

        $this->assertSame(['muitas-rapidas', 'livewire:app.tabela', 'poucas-lentas'], array_map(fn ($l) => $l->rota, $porTempo));
        $this->assertSame('poucas-lentas', $porP95[0]->rota);
        $this->assertSame(['livewire:app.tabela'], array_map(fn ($l) => $l->rota, $livewire));
        $this->assertSame(3.0, $porTempo[0]->sqlMediaQtd);
        $this->assertEqualsWithDelta(25.0, $porTempo[0]->percentualSql, 0.01);
    }

    public function test_sete_dias_le_horas_e_minutos(): void
    {
        $horaAtual = intdiv(self::AGORA, 3600) * 3600;
        $this->repo->gravarAgregados([
            $this->agregado($horaAtual - 7200, 'a', 5, 80),
            $this->agregado($this->minuto(1), 'a', 2, 80),
        ]);
        $this->repo->consolidarHora($horaAtual - 7200);

        $this->assertSame(7, $this->repo->resumo(Periodo::D7, null, self::AGORA)->qtd);
    }

    public function test_requisicoes_lentas_e_detalhe(): void
    {
        $this->repo->gravarDetalhadas([
            new RegistroDeRequisicao(self::AGORA - 100, 'egap', 'x', 'GET', 200, 1500, 10, 2, 30, [], false, 'antiga', ['top_consultas' => [], 'erro' => null]),
            new RegistroDeRequisicao(self::AGORA - 50, 'egap', 'y', 'GET', 500, 2500, 10, 2, 30, [], false, 'recente', ['top_consultas' => [], 'erro' => null]),
            new RegistroDeRequisicao(self::AGORA - 7200, 'egap', 'z', 'GET', 200, 2500, 10, 2, 30, [], false, 'fora', ['top_consultas' => [], 'erro' => null]),
        ]);

        $lentas = $this->repo->requisicoesLentas(Periodo::H1, null, 10, self::AGORA);

        $this->assertSame(['recente', 'antiga'], array_map(fn ($r) => $r->requestId, $lentas));
        $this->assertSame('y', $this->repo->requisicao('recente')->rota);
        $this->assertNull($this->repo->requisicao('nao-existe'));
    }

    public function test_snapshots_e_batimentos(): void
    {
        $this->assertNull($this->repo->ultimoSnapshot());

        $this->repo->gravarSnapshot(new Snapshot(self::AGORA - 120, ['web_interno' => 1], null, null, null));
        $this->repo->gravarSnapshot(new Snapshot(self::AGORA - 60, ['web_interno' => 2], null, null, null));
        $this->repo->registrarBatimento('x', self::AGORA, false, 'erro');

        $this->assertSame(2, $this->repo->ultimoSnapshot()->online['web_interno']);
        $this->assertCount(2, $this->repo->serieSnapshots(Periodo::H1, self::AGORA));
        $this->assertSame(['ultima_execucao' => self::AGORA, 'sucesso' => false, 'mensagem' => 'erro'], $this->repo->batimentos()['x']);
    }
}
