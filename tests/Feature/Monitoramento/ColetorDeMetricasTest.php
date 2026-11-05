<?php

namespace Tests\Feature\Monitoramento;

use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ColetorDeMetricasTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        Log::spy();

        $this->dir = sys_get_temp_dir().'/mon_col_'.uniqid();
        config([
            'monitoramento.ativo' => true,
            'monitoramento.buffer_dir' => $this->dir,
            'monitoramento.amostragem' => 0,
        ]);

        Route::get('/egap/__mon-ok', function () {
            DB::connection('emes')->select('select 1');

            return 'ok';
        })->name('teste.mon.ok');

        Route::get('/egap/__mon-erro', function () {
            DB::connection('emes')->select('select ? as cpf', ['123.456.789-00']);

            throw new RuntimeException('falhou');
        })->name('teste.mon.erro');

        Route::get('/egap/__mon-n1', function () {
            for ($i = 0; $i < 20; $i++) {
                DB::connection('emes')->select('select 1');
            }

            return 'ok';
        })->name('teste.mon.n1');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        @rmdir($this->dir);

        parent::tearDown();
    }

    private function linhas(): array
    {
        $linhas = [];

        foreach (glob($this->dir.'/*.ndjson') ?: [] as $arquivo) {
            foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
                $linhas[] = $linha;
            }
        }

        return $linhas;
    }

    public function test_desligado_nao_grava_nada(): void
    {
        config(['monitoramento.ativo' => false]);

        $this->get('/egap/__mon-ok')->assertOk();

        $this->assertSame([], $this->linhas());
    }

    public function test_grava_uma_linha_com_rota_e_sql(): void
    {
        $resposta = $this->get('/egap/__mon-ok')->assertOk();

        $linhas = $this->linhas();
        $this->assertCount(1, $linhas);

        $registro = RegistroDeRequisicao::fromArray(json_decode($linhas[0], true));
        $this->assertSame('egap', $registro->contexto);
        $this->assertSame('teste.mon.ok', $registro->rota);
        $this->assertSame(200, $registro->status);
        $this->assertSame(1, $registro->sqlQtd);
        $this->assertFalse($registro->nMaisUm);
        $this->assertNull($registro->detalhe);
        $this->assertSame($registro->requestId, $resposta->headers->get('X-Request-Id'));
    }

    public function test_requisicao_com_erro_traz_detalhe_sem_bindings(): void
    {
        $this->get('/egap/__mon-erro')->assertStatus(500);

        $linhas = $this->linhas();
        $this->assertCount(1, $linhas);
        $this->assertStringNotContainsString('123.456.789-00', $linhas[0]);

        $registro = RegistroDeRequisicao::fromArray(json_decode($linhas[0], true));
        $this->assertSame(500, $registro->status);
        $this->assertSame(RuntimeException::class, $registro->detalhe['erro']['class']);
        $this->assertSame('select ? as cpf', $registro->detalhe['top_consultas'][0]['SQL']);
    }

    public function test_requisicao_lenta_traz_detalhe(): void
    {
        config(['monitoramento.limiar_lenta_ms' => 0]);

        $this->get('/egap/__mon-ok')->assertOk();

        $registro = RegistroDeRequisicao::fromArray(json_decode($this->linhas()[0], true));
        $this->assertNotNull($registro->detalhe);
        $this->assertNull($registro->detalhe['erro']);
        $this->assertSame(1, $registro->detalhe['top_consultas'][0]['count']);
    }

    public function test_n_mais_um_marcado_com_20_consultas_iguais(): void
    {
        $this->get('/egap/__mon-n1')->assertOk();

        $registro = RegistroDeRequisicao::fromArray(json_decode($this->linhas()[0], true));
        $this->assertTrue($registro->nMaisUm);
        $this->assertSame(20, $registro->sqlQtd);
    }

    public function test_proprio_painel_e_ignorado(): void
    {
        Route::get('/egap/admin-egap/monitoramento', fn () => 'ok');

        $this->get('/egap/admin-egap/monitoramento')->assertOk();

        $this->assertSame([], $this->linhas());
    }

    public function test_falha_no_buffer_nao_afeta_a_resposta(): void
    {
        $this->app->instance(BufferDeRequisicoes::class, new class('x') extends BufferDeRequisicoes
        {
            public function anexar(RegistroDeRequisicao $r): void
            {
                throw new RuntimeException('disco cheio');
            }
        });

        $this->get('/egap/__mon-ok')->assertOk()->assertSee('ok');
    }
}
