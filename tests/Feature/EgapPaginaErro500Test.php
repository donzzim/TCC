<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class EgapPaginaErro500Test extends TestCase
{
    private const SEGREDO = 'SQLSTATE[42S02]: tabela egap_tabela_secreta não existe';

    protected function setUp(): void
    {
        parent::setUp();

        Log::spy();

        Route::get('/egap/__teste-erro-500', fn () => throw new RuntimeException(self::SEGREDO));
        Route::get('/api/__teste-erro-500', fn () => throw new RuntimeException(self::SEGREDO));
        Route::get('/egap/__teste-abort-500', fn () => abort(500, self::SEGREDO));
    }

    private function ambiente(string $env, bool $debug): void
    {
        $this->app['env'] = $env;
        config(['app.debug' => $debug]);
    }

    public function test_html_mostra_pagina_generica_mesmo_com_debug_em_producao(): void
    {
        $this->ambiente('production', true);

        $resposta = $this->get('/egap/__teste-erro-500');

        $resposta->assertStatus(500)
            ->assertSee('Algo deu errado')
            ->assertSee('Código da ocorrência')
            ->assertDontSee('egap_tabela_secreta')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('RuntimeException')
            ->assertDontSee('bootstrap', false);
    }

    public function test_abort_500_com_mensagem_nao_vaza_a_mensagem(): void
    {
        $this->ambiente('production', false);

        $this->getJson('/egap/__teste-abort-500')
            ->assertStatus(500)
            ->assertJsonStructure(['message', 'ocorrencia'])
            ->assertDontSee('egap_tabela_secreta');
    }

    public function test_json_e_api_recebem_resposta_generica(): void
    {
        $this->ambiente('production', true);

        $resposta = $this->get('/api/__teste-erro-500');

        $resposta->assertStatus(500)
            ->assertJsonPath('message', 'Erro interno. Tente novamente mais tarde.')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace')
            ->assertDontSee('egap_tabela_secreta');
    }

    public function test_codigo_da_ocorrencia_e_gravado_no_log_com_o_erro_real(): void
    {
        $this->ambiente('production', false);

        $ocorrencia = $this->getJson('/api/__teste-erro-500')->json('ocorrencia');

        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}$/', $ocorrencia);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($msg, $ctx = []) => $msg === 'Erro 500 exibido ao usuário'
                && $ctx['ocorrencia'] === $ocorrencia
                && $ctx['message'] === self::SEGREDO)
            ->once();
    }

    public function test_local_com_debug_mantem_tela_detalhada_do_desenvolvedor(): void
    {
        $this->ambiente('local', true);

        $this->get('/egap/__teste-erro-500')
            ->assertStatus(500)
            ->assertSee('RuntimeException - Internal Server Error', false);
    }

    public function test_erros_4xx_nao_sao_afetados(): void
    {
        $this->ambiente('production', false);

        $this->get('/egap/__rota-que-nao-existe')->assertStatus(404);
    }
}
