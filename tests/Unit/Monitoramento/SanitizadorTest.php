<?php

namespace Tests\Unit\Monitoramento;

use App\Monitoramento\Logs\Sanitizador;
use PHPUnit\Framework\TestCase;

class SanitizadorTest extends TestCase
{
    public function test_mascara_cpf_token_e_senha(): void
    {
        $t = Sanitizador::limpar('cpf 123.456.789-09 e 12345678909 Bearer abc.def {"password":"x1","senha":"y"} 5|'.str_repeat('a', 40).' token=zzz9');

        foreach (['123.456.789-09', '12345678909', 'abc.def', 'x1', '"y"', str_repeat('a', 40), 'zzz9'] as $segredo) {
            $this->assertStringNotContainsString($segredo, $t);
        }
        $this->assertStringContainsString('cpf ***.***.***-**', $t);
        $this->assertStringContainsString('Bearer ***', $t);
    }

    public function test_preserva_texto_comum(): void
    {
        $texto = 'SQLSTATE[42S02]: tabela egap_bens não existe (linha 120, id 4512)';

        $this->assertSame($texto, Sanitizador::limpar($texto));
    }
}
