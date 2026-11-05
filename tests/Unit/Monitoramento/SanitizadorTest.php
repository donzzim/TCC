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

    public function test_mascara_chaves_compostas_e_authorization(): void
    {
        $t = Sanitizador::limpar('{"password_confirmation":"a1","current_password":"a2","_token":"a3","remember_token":"a4","access_token":"a5","client_secret":"a6","senha_nova":"a7"} senha_atual=a8 Authorization: Basic YWRtaW46c2VjcmV0');

        foreach (['a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'YWRtaW46c2VjcmV0'] as $segredo) {
            $this->assertStringNotContainsString($segredo, $t);
        }
    }

    public function test_omite_sql_com_valores_de_query_exception(): void
    {
        $mensagem = 'SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry (Connection: emes, SQL: insert into users (email, name) values (fulano@tj.jus.br, Fulano de Tal))';
        $noContexto = '{"exception":"[object] (Illuminate\\\\Database\\\\QueryException(code: 23000): SQLSTATE[23000]: erro (Connection: emes, SQL: select * from users where cpf = 12345678909 and nome = Fulano) at /app/X.php:10)"} []';

        $t = Sanitizador::limpar($mensagem);
        $c = Sanitizador::limpar($noContexto);

        $this->assertStringNotContainsString('fulano@tj.jus.br', $t);
        $this->assertStringNotContainsString('Fulano de Tal', $t);
        $this->assertStringContainsString('Duplicate entry (Connection: emes, SQL omitida)', $t);
        $this->assertStringNotContainsString('nome = Fulano', $c);
        $this->assertStringContainsString('SQL omitida) at /app/X.php:10)', $c);
    }

    public function test_omite_sql_no_formato_do_laravel_12(): void
    {
        $t = Sanitizador::limpar('SQLSTATE[HY000]: General error: 1449 (Connection: emes, Host: 127.0.0.1, Port: 3306, Database: emes-egap, SQL: select count(*) as aggregate from `egap_bens` where `patrimonio_id` = 4512 and `cpf` = 98765432100)');

        $this->assertStringNotContainsString('4512', $t);
        $this->assertStringContainsString('(Connection: emes, Host: 127.0.0.1, Port: 3306, Database: emes-egap, SQL omitida)', $t);
    }

    public function test_preserva_texto_comum(): void
    {
        $texto = 'SQLSTATE[42S02]: tabela egap_bens não existe (linha 120, id 4512)';

        $this->assertSame($texto, Sanitizador::limpar($texto));
    }
}
