<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Coleta\Snapshot;
use App\Monitoramento\Leitura\ResumoDoPeriodo;
use App\Monitoramento\Leitura\SemaforoDeSaude;
use PHPUnit\Framework\TestCase;

class SemaforoDeSaudeTest extends TestCase
{
    private function resumo(?float $p95 = 100, float $taxa = 0): ResumoDoPeriodo
    {
        return new ResumoDoPeriodo(qtd: 100, erros5xx: (int) ($taxa * 100), erros4xx: 0, p50: 50, p95: $p95, p99: $p95, reqPorMin: 10, taxaErro5xx: $taxa);
    }

    private function snapshot(int $instante = 1000, ?array $mysql = null, ?array $fila = null): Snapshot
    {
        return new Snapshot($instante, null, $mysql, $fila, null);
    }

    public function test_tudo_bem_e_verde(): void
    {
        $a = SemaforoDeSaude::avaliar($this->resumo(), $this->snapshot(), 1060);

        $this->assertSame(['nivel' => 'verde', 'motivos' => []], $a);
    }

    public function test_p95(): void
    {
        $this->assertSame('amarelo', SemaforoDeSaude::avaliar($this->resumo(1500), $this->snapshot(), 1060)['nivel']);
        $this->assertSame('vermelho', SemaforoDeSaude::avaliar($this->resumo(5000), $this->snapshot(), 1060)['nivel']);
        $this->assertSame('verde', SemaforoDeSaude::avaliar($this->resumo(null), $this->snapshot(), 1060)['nivel']);
    }

    public function test_taxa_de_erro(): void
    {
        $this->assertSame('amarelo', SemaforoDeSaude::avaliar($this->resumo(taxa: 0.02), $this->snapshot(), 1060)['nivel']);
        $a = SemaforoDeSaude::avaliar($this->resumo(taxa: 0.06), $this->snapshot(), 1060);
        $this->assertSame('vermelho', $a['nivel']);
        $this->assertSame(['6% de erros 5xx'], $a['motivos']);
    }

    public function test_coleta_parada(): void
    {
        $this->assertSame('amarelo', SemaforoDeSaude::avaliar($this->resumo(), $this->snapshot(1000), 1000 + 240)['nivel']);
        $this->assertSame('amarelo', SemaforoDeSaude::avaliar($this->resumo(), null, 1000)['nivel']);
    }

    public function test_fila(): void
    {
        $parada = $this->snapshot(fila: ['pendentes' => 3, 'idade_mais_antigo_seg' => 900, 'worker' => ['running' => true]]);
        $this->assertSame('amarelo', SemaforoDeSaude::avaliar($this->resumo(), $parada, 1060)['nivel']);

        $semWorker = $this->snapshot(fila: ['pendentes' => 3, 'idade_mais_antigo_seg' => 10, 'worker' => ['running' => false]]);
        $this->assertSame('vermelho', SemaforoDeSaude::avaliar($this->resumo(), $semWorker, 1060)['nivel']);

        $vazia = $this->snapshot(fila: ['pendentes' => 0, 'idade_mais_antigo_seg' => null, 'worker' => ['running' => false]]);
        $this->assertSame('verde', SemaforoDeSaude::avaliar($this->resumo(), $vazia, 1060)['nivel']);
    }

    public function test_duracoes_longas_em_horas_e_dias(): void
    {
        $fila = $this->snapshot(fila: ['pendentes' => 3, 'idade_mais_antigo_seg' => 135461 * 60, 'worker' => ['running' => true]]);
        $this->assertContains('fila parada há 94 dias', SemaforoDeSaude::avaliar($this->resumo(), $fila, 1060)['motivos']);

        $this->assertContains('coleta parada há 2 h', SemaforoDeSaude::avaliar($this->resumo(), $this->snapshot(1000), 1000 + 7300)['motivos']);
        $this->assertContains('coleta parada há 6 min', SemaforoDeSaude::avaliar($this->resumo(), $this->snapshot(1000), 1000 + 387)['motivos']);
    }

    public function test_conexoes_do_mysql(): void
    {
        $this->assertSame('amarelo', SemaforoDeSaude::avaliar($this->resumo(), $this->snapshot(mysql: ['threads_connected' => 110, 'max_connections' => 151]), 1060)['nivel']);
        $this->assertSame('verde', SemaforoDeSaude::avaliar($this->resumo(), $this->snapshot(mysql: ['threads_connected' => 10, 'max_connections' => 151]), 1060)['nivel']);
    }

    public function test_pior_nivel_vence_e_lista_motivos(): void
    {
        $r = new ResumoDoPeriodo(qtd: 100, erros5xx: 2, erros4xx: 0, p50: 80, p95: 1500, p99: 2000, reqPorMin: 10, taxaErro5xx: 0.02);
        $s = new Snapshot(instante: 1000, online: null, mysql: ['threads_connected' => 140, 'max_connections' => 151], fila: null, servidor: null);

        $a = SemaforoDeSaude::avaliar($r, $s, 1060);

        $this->assertSame('vermelho', $a['nivel']);
        $this->assertCount(3, $a['motivos']);
    }
}
