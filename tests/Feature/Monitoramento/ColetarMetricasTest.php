<?php

namespace Tests\Feature\Monitoramento;

use App\Monitoramento\Agregacao\AgregadoMinuto;
use App\Monitoramento\Agregacao\Histograma;
use App\Monitoramento\Armazenamento\RepositorioDeMetricas;
use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Monitoramento\Concerns\UsaBancoDeMetricas;
use Tests\TestCase;

class ColetarMetricasTest extends TestCase
{
    use UsaBancoDeMetricas;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usarBancoDeMetricas();
        $this->dir = sys_get_temp_dir().'/mon_coletar_'.uniqid().'/buffer';
        config(['monitoramento.ativo' => true, 'monitoramento.buffer_dir' => $this->dir]);
        $this->app->forgetInstance(BufferDeRequisicoes::class);
        Carbon::setTestNow(Carbon::createFromTimestamp(1_700_000_000));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        array_map('unlink', glob(dirname($this->dir).'/*/*') ?: []);
        array_map('unlink', glob(dirname($this->dir).'/*.lock') ?: []);
        @rmdir($this->dir);
        @rmdir(dirname($this->dir));

        parent::tearDown();
    }

    private function anexar(int $quantos, float $instante, int $status = 200, ?array $detalhe = null): void
    {
        $buffer = app(BufferDeRequisicoes::class);

        for ($i = 0; $i < $quantos; $i++) {
            $buffer->anexar(new RegistroDeRequisicao($instante, 'egap', 'rota.x', 'GET', $status, 100, 10, 2, 12, [], false, uniqid('', true), $detalhe));
        }
    }

    private function minutos()
    {
        return DB::connection('monitoramento')->table('agregados_minuto')->get();
    }

    private function agregado(int $minuto, int $qtd, string $rota = 'r'): AgregadoMinuto
    {
        $faixas = Histograma::vazio();
        $faixas[1] = $qtd;

        return new AgregadoMinuto($minuto, 'egap', $rota, $qtd, 0, 0, 100.0 * $qtd, 100.0, 10.0, 2, 12.0, 0, $faixas);
    }

    public function test_coletar_grava_agregados_e_apaga_o_buffer(): void
    {
        $this->anexar(3, 1_700_000_000 - 120);
        $this->anexar(1, 1_700_000_000 - 120, 500, ['top_consultas' => [], 'erro' => null]);

        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $linhas = $this->minutos();
        $this->assertCount(1, $linhas);
        $this->assertSame(4, (int) $linhas[0]->qtd);
        $this->assertSame(1, (int) $linhas[0]->erros_5xx);
        $this->assertSame(1, DB::connection('monitoramento')->table('requisicoes')->count());
        $this->assertSame([], glob($this->dir.'/*'));
    }

    public function test_minuto_atual_fica_no_buffer(): void
    {
        $this->anexar(2, 1_700_000_000);

        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $this->assertCount(0, $this->minutos());
        $this->assertCount(1, glob($this->dir.'/*.ndjson'));
    }

    public function test_arquivo_ja_em_processamento_nao_e_lido_de_novo(): void
    {
        $this->anexar(3, 1_700_000_000 - 120);
        $this->artisan('monitoramento:coletar')->assertSuccessful();
        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $this->assertSame(3, (int) $this->minutos()[0]->qtd);

        $this->anexar(2, 1_700_000_000 - 110);
        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $linhas = $this->minutos();
        $this->assertCount(1, $linhas);
        $this->assertSame(5, (int) $linhas[0]->qtd);
        $this->assertSame(5, json_decode($linhas[0]->faixas, true)[1]);
    }

    public function test_reprocessar_arquivo_que_nao_foi_apagado_nao_conta_duas_vezes(): void
    {
        $this->app->instance(BufferDeRequisicoes::class, new class($this->dir) extends BufferDeRequisicoes
        {
            public function descartar(string $arquivo): void
            {
            }
        });
        $this->anexar(3, 1_700_000_000 - 120);
        $this->artisan('monitoramento:coletar')->assertSuccessful();

        [$pendente] = glob($this->dir.'/*.processando');
        touch($pendente, time() - 1200);
        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $this->assertSame(3, (int) $this->minutos()[0]->qtd);
        $this->assertSame(1, DB::connection('monitoramento')->table('arquivos_processados')->count());
    }

    public function test_consolidar_hora_e_idempotente(): void
    {
        $repo = app(RepositorioDeMetricas::class);
        $hora = intdiv(1_700_000_000, 3600) * 3600 - 3600;
        $repo->gravarAgregados([$this->agregado($hora, 2), $this->agregado($hora + 60, 3), $this->agregado($hora + 3600, 7)]);

        $repo->consolidarHora($hora);
        $repo->consolidarHora($hora);

        $horas = DB::connection('monitoramento')->table('agregados_hora')->get();
        $this->assertCount(1, $horas);
        $this->assertSame(5, (int) $horas[0]->qtd);
        $this->assertSame($hora, (int) $horas[0]->hora);
        $this->assertSame(5, json_decode($horas[0]->faixas, true)[1]);
    }

    public function test_limpar_respeita_retencoes(): void
    {
        $repo = app(RepositorioDeMetricas::class);
        $agora = 1_700_000_000;
        $repo->gravarAgregados([$this->agregado($agora - 49 * 3600, 1, 'velha'), $this->agregado($agora - 47 * 3600, 1, 'nova')]);
        DB::connection('monitoramento')->table('agregados_hora')->insert([
            ['hora' => $agora - 31 * 86400, 'contexto' => 'egap', 'rota' => 'velha', 'faixas' => '[]'],
            ['hora' => $agora - 29 * 86400, 'contexto' => 'egap', 'rota' => 'nova', 'faixas' => '[]'],
        ]);

        $repo->limpar($agora);

        $this->assertSame(['nova'], $this->minutos()->pluck('rota')->all());
        $this->assertSame(['nova'], DB::connection('monitoramento')->table('agregados_hora')->pluck('rota')->all());
    }

    public function test_inativo_nao_grava(): void
    {
        config(['monitoramento.ativo' => false]);
        $this->anexar(3, 1_700_000_000 - 120);

        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $this->assertCount(0, $this->minutos());
        $this->assertSame(0, DB::connection('monitoramento')->table('batimentos')->count());
    }

    public function test_primeira_execucao_cria_o_banco_de_metricas(): void
    {
        $arquivo = dirname($this->dir).'/novo/metricas.sqlite';
        config(['database.connections.monitoramento.database' => $arquivo]);
        DB::purge('monitoramento');
        $this->anexar(2, 1_700_000_000 - 120);

        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $this->assertFileExists($arquivo);
        $this->assertSame(2, (int) $this->minutos()[0]->qtd);

        DB::purge('monitoramento');
        @unlink($arquivo);
        @unlink($arquivo.'-wal');
        @unlink($arquivo.'-shm');
        @rmdir(dirname($arquivo));
    }

    public function test_registra_batimento(): void
    {
        $this->artisan('monitoramento:coletar')->assertSuccessful();

        $batimento = DB::connection('monitoramento')->table('batimentos')->where('tarefa', 'monitoramento:coletar')->first();
        $this->assertNotNull($batimento);
        $this->assertSame(1_700_000_000, (int) $batimento->ultima_execucao);
        $this->assertSame(1, (int) $batimento->sucesso);
    }
}
