<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Agregacao\Histograma;
use PHPUnit\Framework\TestCase;

class HistogramaTest extends TestCase
{
    public function test_percentil_95_cai_na_faixa_certa(): void
    {
        $f = Histograma::vazio();
        for ($i = 0; $i < 95; $i++) {
            $f[Histograma::faixaDe(80)]++;
        }
        for ($i = 0; $i < 5; $i++) {
            $f[Histograma::faixaDe(1800)]++;
        }

        $this->assertSame(100.0, Histograma::percentil($f, 0.95));
        $this->assertSame(2000.0, Histograma::percentil($f, 0.99));
        $this->assertNull(Histograma::percentil(Histograma::vazio(), 0.95));
    }

    public function test_limites_das_faixas(): void
    {
        $this->assertSame(0, Histograma::faixaDe(0));
        $this->assertSame(0, Histograma::faixaDe(50));
        $this->assertSame(1, Histograma::faixaDe(50.01));
        $this->assertSame(11, Histograma::faixaDe(10000));
        $this->assertSame(12, Histograma::faixaDe(20000));
        $this->assertCount(13, Histograma::vazio());
    }

    public function test_ultima_faixa_retorna_dez_segundos(): void
    {
        $f = Histograma::vazio();
        $f[12] = 3;

        $this->assertSame(10000.0, Histograma::percentil($f, 0.5));
    }

    public function test_somar(): void
    {
        $a = Histograma::vazio();
        $b = Histograma::vazio();
        $a[0] = 2;
        $b[0] = 3;
        $b[12] = 1;

        $soma = Histograma::somar($a, $b);

        $this->assertSame(5, $soma[0]);
        $this->assertSame(1, $soma[12]);
    }
}
