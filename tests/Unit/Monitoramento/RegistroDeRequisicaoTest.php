<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Coleta\RegistroDeRequisicao;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RegistroDeRequisicaoTest extends TestCase
{
    private function dados(): array
    {
        return [
            'instante' => 1700000000.5,
            'contexto' => 'egap',
            'rota' => 'filament.egap.home',
            'metodo' => 'GET',
            'status' => 200,
            'duracaoMs' => 123.4,
            'sqlMs' => 20.0,
            'sqlQtd' => 3,
            'memoriaMb' => 18.0,
            'livewire' => ['app.x'],
            'nMaisUm' => false,
            'requestId' => 'abc',
            'detalhe' => ['top_consultas' => [], 'erro' => null],
        ];
    }

    public function test_fromArray_de_toArray_e_identico(): void
    {
        $registro = RegistroDeRequisicao::fromArray($this->dados());

        $this->assertSame($this->dados(), $registro->toArray());
        $this->assertEquals($registro, RegistroDeRequisicao::fromArray($registro->toArray()));
    }

    public function test_fromArray_sem_chave_lanca_excecao(): void
    {
        $dados = $this->dados();
        unset($dados['rota']);

        $this->expectException(InvalidArgumentException::class);
        RegistroDeRequisicao::fromArray($dados);
    }

    public function test_detalhe_e_opcional(): void
    {
        $dados = $this->dados();
        unset($dados['detalhe']);

        $this->assertNull(RegistroDeRequisicao::fromArray($dados)->detalhe);
    }
}
