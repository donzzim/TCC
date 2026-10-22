<?php

namespace Tests\Unit;

use App\Filament\Egap\Auth\Concerns\ResolveLotacaoEgap;
use App\Models\egap\Cadastro\Setores;
use PHPUnit\Framework\TestCase;

class ResolveLotacaoEgapTest extends TestCase
{
    private function resolvedor(): object
    {
        return new class
        {
            use ResolveLotacaoEgap;
        };
    }

    private function lotacoes()
    {
        return collect([
            (new Setores())->forceFill(['id' => 1, 'codigodauo' => 10]),
            (new Setores())->forceFill(['id' => 2, 'codigodauo' => 20]),
        ]);
    }

    public function test_usa_a_lotacao_escolhida_quando_ela_esta_na_colecao(): void
    {
        $this->assertSame(
            ['setor' => 2, 'codigodauo' => 20],
            $this->resolvedor()::escolherLotacao($this->lotacoes(), 2),
        );
    }

    public function test_cai_na_primeira_quando_a_escolhida_nao_esta_na_colecao(): void
    {
        $this->assertSame(
            ['setor' => 1, 'codigodauo' => 10],
            $this->resolvedor()::escolherLotacao($this->lotacoes(), 99),
        );
    }

    public function test_cai_na_primeira_quando_nada_foi_escolhido(): void
    {
        $this->assertSame(
            ['setor' => 1, 'codigodauo' => 10],
            $this->resolvedor()::escolherLotacao($this->lotacoes(), null),
        );
    }

    public function test_retorna_null_para_colecao_vazia(): void
    {
        $this->assertNull($this->resolvedor()::escolherLotacao(collect(), 1));
    }

    public function test_codigodauo_vazio_vira_null(): void
    {
        $this->assertSame(
            ['setor' => 3, 'codigodauo' => null],
            $this->resolvedor()::escolherLotacao(collect([(new Setores())->forceFill(['id' => 3, 'codigodauo' => null])]), 3),
        );
    }
}
