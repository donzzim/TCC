<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Coleta\BufferDeRequisicoes;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use PHPUnit\Framework\TestCase;

class BufferDeRequisicoesTest extends TestCase
{
    private array $diretorios = [];

    protected function tearDown(): void
    {
        foreach ($this->diretorios as $dir) {
            array_map('unlink', glob("$dir/*") ?: []);
            @rmdir($dir);
        }

        parent::tearDown();
    }

    private function dirTemporario(): string
    {
        return $this->diretorios[] = sys_get_temp_dir().'/mon_buf_'.uniqid();
    }

    private function registro(float $instante = 1_700_000_000, string $id = 'r'): RegistroDeRequisicao
    {
        return new RegistroDeRequisicao($instante, 'egap', 'rota', 'GET', 200, 10, 1, 1, 8, [], false, $id.uniqid());
    }

    public function test_anexa_no_arquivo_do_minuto(): void
    {
        $b = new BufferDeRequisicoes($dir = $this->dirTemporario());

        $b->anexar($this->registro(1_700_000_000));
        $b->anexar($this->registro(1_700_000_030));

        $this->assertSame(["$dir/202311142213.ndjson"], glob("$dir/*.ndjson"));
        $this->assertCount(2, file("$dir/202311142213.ndjson", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }

    public function test_reserva_so_minutos_fechados(): void
    {
        $b = new BufferDeRequisicoes($dir = $this->dirTemporario());
        $b->anexar($this->registro(1_700_000_000));
        $b->anexar($this->registro(1_700_000_060));

        $reservados = $b->reservarProntos(1_700_000_070);

        $this->assertSame(["$dir/202311142213.processando"], $reservados);
        $this->assertSame(["$dir/202311142214.ndjson"], glob("$dir/*.ndjson"));
        $this->assertSame([], $b->reservarProntos(1_700_000_070));
    }

    public function test_linha_invalida_e_ignorada(): void
    {
        $b = new BufferDeRequisicoes($dir = $this->dirTemporario());
        $b->anexar($this->registro(1_700_000_000));
        file_put_contents("$dir/202311142213.ndjson", "{quebrado\n\n".json_encode(['rota' => 'sem campos'])."\n", FILE_APPEND);
        $b->anexar($this->registro(1_700_000_001));

        [$arquivo] = $b->reservarProntos(1_700_000_000 + 120);

        $this->assertCount(2, iterator_to_array($b->ler($arquivo), false));
    }

    public function test_descartar_remove_o_arquivo(): void
    {
        $b = new BufferDeRequisicoes($dir = $this->dirTemporario());
        $b->anexar($this->registro(1_700_000_000));
        [$arquivo] = $b->reservarProntos(1_700_000_200);

        $b->descartar($arquivo);

        $this->assertFileDoesNotExist($arquivo);
    }

    public function test_pendentes_antigos(): void
    {
        $b = new BufferDeRequisicoes($dir = $this->dirTemporario());
        $b->anexar($this->registro(1_700_000_000));
        [$arquivo] = $b->reservarProntos(1_700_000_200);
        touch($arquivo, time() - 1200);

        $this->assertSame([$arquivo], $b->pendentesAntigos(time(), 600));
        $this->assertSame([], $b->pendentesAntigos(time(), 3600));
    }

    public function test_anexar_em_diretorio_invalido_nao_lanca(): void
    {
        (new BufferDeRequisicoes("Z:/nao/existe/".uniqid()."\0"))->anexar($this->registro());

        $this->addToAssertionCount(1);
    }
}
