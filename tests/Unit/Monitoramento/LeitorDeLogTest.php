<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Logs\AgrupadorDeExcecoes;
use App\Monitoramento\Logs\LeitorDeLog;
use PHPUnit\Framework\TestCase;

class LeitorDeLogTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/mon_logs_'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*') ?: []);
        rmdir($this->dir);

        parent::tearDown();
    }

    private function log(string $conteudo, string $nome = 'laravel.log'): string
    {
        file_put_contents($arquivo = $this->dir.'/'.$nome, $conteudo);

        return $arquivo;
    }

    public function test_le_somente_o_fim_do_arquivo(): void
    {
        $arquivo = $this->log(
            str_repeat("[2026-11-05 10:00:00] local.INFO: antiga\n", 100_000)
            ."[2026-11-05 11:00:00] local.ERROR: recente {\"exception\":\"RuntimeException\"} []\n#0 trace\n"
        );
        $antes = memory_get_usage();

        $entradas = (new LeitorDeLog($this->dir))->ultimas($arquivo, maxBytes: 4096, limite: 1);

        $this->assertSame('recente', $entradas[0]->mensagem);
        $this->assertSame('ERROR', $entradas[0]->nivel);
        $this->assertSame('local', $entradas[0]->ambiente);
        $this->assertSame(['exception' => 'RuntimeException'], $entradas[0]->contexto);
        $this->assertStringContainsString('#0 trace', $entradas[0]->trace);
        $this->assertLessThan(2_000_000, memory_get_usage() - $antes);
    }

    public function test_filtra_por_nivel_e_texto_mais_recente_primeiro(): void
    {
        $arquivo = $this->log(
            "[2026-11-05 10:00:00] local.ERROR: primeira falha\n"
            ."[2026-11-05 10:01:00] local.INFO: tudo certo\n"
            ."[2026-11-05 10:02:00] local.ERROR: segunda falha\n"
        );
        $leitor = new LeitorDeLog($this->dir);

        $this->assertSame(['segunda falha', 'primeira falha'], array_column($leitor->ultimas($arquivo, nivel: 'error'), 'mensagem'));
        $this->assertSame(['tudo certo'], array_column($leitor->ultimas($arquivo, texto: 'CERTO'), 'mensagem'));
    }

    public function test_mascara_dados_sensiveis_na_leitura(): void
    {
        $arquivo = $this->log("[2026-11-05 10:00:00] local.ERROR: login falhou cpf 123.456.789-09 {\"password\":\"segredo\"} []\n");

        $e = (new LeitorDeLog($this->dir))->ultimas($arquivo)[0];

        $this->assertStringNotContainsString('123.456.789-09', $e->mensagem);
        $this->assertSame('***', $e->contexto['password']);
    }

    public function test_busca_ocorrencia(): void
    {
        $this->log("[2026-11-05 09:00:00] local.ERROR: outra coisa\n", 'laravel-2026-11-04.log');
        $this->log(
            "[2026-11-05 10:00:00] local.INFO: antes\n"
            ."[2026-11-05 10:05:00] local.ERROR: Erro 500 exibido ao usuário {\"ocorrencia\":\"A1B2C3D4\",\"exception\":\"RuntimeException\",\"file\":\"/app/X.php\",\"line\":12} []\n"
            ."[2026-11-05 10:06:00] local.INFO: depois\n"
        );
        $leitor = new LeitorDeLog($this->dir);

        $achada = $leitor->buscarOcorrencia('a1b2c3d4');

        $this->assertSame('Erro 500 exibido ao usuário', $achada->mensagem);
        $this->assertSame('A1B2C3D4', $achada->contexto['ocorrencia']);
        $this->assertNull($leitor->buscarOcorrencia('FFFFFFFF'));
        $this->assertNull($leitor->buscarOcorrencia('../../etc'));
    }

    public function test_arquivos_mais_recente_primeiro(): void
    {
        $antigo = $this->log('x', 'laravel-2026-11-01.log');
        touch($antigo, time() - 3600);
        $novo = $this->log('y', 'laravel.log');

        $this->assertSame([$novo, $antigo], (new LeitorDeLog($this->dir))->arquivos());
    }

    public function test_contexto_com_stacktrace_em_varias_linhas(): void
    {
        $arquivo = $this->log(
            "[2026-11-05 15:34:40] local.ERROR: Arquivo não existe {\"userId\":7,\"exception\":\"[object] (Illuminate\\\\View\\\\ViewException(code: 0): Arquivo não existe at D:\\\\app\\\\Connection.php:838)\n"
            ."[stacktrace]\n"
            ."#0 D:/app/Engine.php(58): Engine->handle()\n"
            ."#1 {main}\n"
            ."\"} \n"
            ."[2026-11-05 15:35:00] local.INFO: depois\n"
        );

        $entrada = (new LeitorDeLog($this->dir))->ultimas($arquivo, nivel: 'ERROR')[0];

        $this->assertSame('Arquivo não existe', $entrada->mensagem);
        $this->assertSame(7, $entrada->contexto['userId']);
        $this->assertStringStartsWith('[object] (Illuminate\\View\\ViewException(code: 0)', $entrada->contexto['exception']);
        $this->assertStringNotContainsString('[stacktrace]', $entrada->contexto['exception']);
        $this->assertStringContainsString('#0 D:/app/Engine.php(58)', $entrada->trace);

        $grupo = AgrupadorDeExcecoes::agrupar([$entrada])[0];
        $this->assertSame(['Illuminate\\View\\ViewException', 'D:\\app\\Connection.php:838'], [$grupo['classe'], $grupo['local']]);
    }

    public function test_agrupa_excecoes_por_classe_e_local(): void
    {
        $arquivo = $this->log(
            "[2026-11-05 10:00:00] local.ERROR: Erro 500 exibido ao usuário {\"exception\":\"RuntimeException\",\"file\":\"/app/A.php\",\"line\":10,\"url\":\"http://x/egap/a\"} []\n"
            ."[2026-11-05 10:05:00] local.ERROR: Erro 500 exibido ao usuário {\"exception\":\"RuntimeException\",\"file\":\"/app/A.php\",\"line\":10,\"url\":\"http://x/egap/a\"} []\n"
            ."[2026-11-05 10:06:00] local.ERROR: Tabela não existe {\"exception\":\"[object] (Illuminate\\\\Database\\\\QueryException(code: 42S02): Tabela não existe at /app/B.php:55)\"} []\n"
            ."[2026-11-05 10:07:00] local.INFO: ignorada\n"
        );

        $grupos = AgrupadorDeExcecoes::agrupar((new LeitorDeLog($this->dir))->ultimas($arquivo));

        $this->assertCount(2, $grupos);
        $this->assertSame(['RuntimeException', '/app/A.php:10', 2, '2026-11-05 10:00:00', '2026-11-05 10:05:00', 'http://x/egap/a'], [
            $grupos[0]['classe'], $grupos[0]['local'], $grupos[0]['qtd'], $grupos[0]['primeira'], $grupos[0]['ultima'], $grupos[0]['url'],
        ]);
        $this->assertSame(['Illuminate\\Database\\QueryException', '/app/B.php:55', 1], [$grupos[1]['classe'], $grupos[1]['local'], $grupos[1]['qtd']]);
    }
}
