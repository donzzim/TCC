<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Agregacao\AgregadorDeRequisicoes;
use App\Monitoramento\Agregacao\Histograma;
use App\Monitoramento\Coleta\RegistroDeRequisicao;
use PHPUnit\Framework\TestCase;

class AgregadorDeRequisicoesTest extends TestCase
{
    private function reg(float $instante, string $rota, float $duracao, int $status, ?array $detalhe = null, string $contexto = 'egap', bool $nMaisUm = false): RegistroDeRequisicao
    {
        return new RegistroDeRequisicao($instante, $contexto, $rota, 'GET', $status, $duracao, 5, 2, 10, [], $nMaisUm, uniqid(), $detalhe);
    }

    public function test_agrega_por_minuto_contexto_e_rota(): void
    {
        $r = AgregadorDeRequisicoes::agregar([
            $this->reg(120.5, 'a', 80, 200),
            $this->reg(150.0, 'a', 1800, 500, ['top_consultas' => [], 'erro' => null]),
            $this->reg(190.0, 'a', 10, 404),
        ]);

        [$m120, $m180] = $r['agregados'];
        $this->assertSame([120, 2, 1, 0, 1880.0, 1800.0], [$m120->minuto, $m120->qtd, $m120->erros5xx, $m120->erros4xx, $m120->somaMs, $m120->maxMs]);
        $this->assertSame([180, 1, 1], [$m180->minuto, $m180->qtd, $m180->erros4xx]);
        $this->assertCount(1, $r['detalhados']);
    }

    public function test_separa_contexto_e_rota_e_soma_sql_e_faixas(): void
    {
        $r = AgregadorDeRequisicoes::agregar([
            $this->reg(60, 'a', 80, 200, nMaisUm: true),
            $this->reg(61, 'b', 80, 200),
            $this->reg(62, 'a', 80, 200, contexto: 'mobile-api'),
            $this->reg(63, 'a', 400, 200),
        ]);

        $this->assertCount(3, $r['agregados']);
        $a = $r['agregados'][0];
        $this->assertSame(['egap', 'a', 2, 10.0, 4, 1], [$a->contexto, $a->rota, $a->qtd, $a->somaSqlMs, $a->somaSqlQtd, $a->nMaisUm]);
        $this->assertSame(1, $a->faixas[Histograma::faixaDe(80)]);
        $this->assertSame(1, $a->faixas[Histograma::faixaDe(400)]);
        $this->assertSame(10.0, $a->maxMemoriaMb);
    }

    public function test_vazio(): void
    {
        $this->assertSame(['agregados' => [], 'detalhados' => []], AgregadorDeRequisicoes::agregar([]));
    }
}
