<?php

namespace Tests\Unit;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EgapIdEgapSessaoTest extends TestCase
{
    public function test_it_searches_both_cpf_formats_stored_in_the_legacy_table(): void
    {
        $this->assertSame(
            ['123.456.789-09', '12345678909'],
            EgapSession::formatosDeBusca('123.456.789-09'),
        );

        $this->assertSame(
            ['123.456.789-09', '12345678909'],
            EgapSession::formatosDeBusca('12345678909'),
        );
    }

    public function test_it_refuses_to_search_an_incomplete_cpf(): void
    {
        $this->assertSame([], EgapSession::formatosDeBusca('123'));
        $this->assertSame([], EgapSession::formatosDeBusca(null));
        $this->assertSame([], EgapSession::formatosDeBusca(''));
    }

    public function test_it_reuses_the_session_value_without_touching_the_database(): void
    {
        $this->autenticar(id: 99, cpf: '12345678909');

        session([EgapSession::SESSION_KEY => ['user_id' => 99, 'egap_id' => 4676]]);

        $this->assertSame(4676, EgapSession::resolverAtual());
    }

    public function test_it_caches_the_absence_of_a_legacy_record(): void
    {
        $this->autenticar(id: 99, cpf: '12345678909');

        session([EgapSession::SESSION_KEY => ['user_id' => 99, 'egap_id' => null]]);

        $this->assertNull(EgapSession::resolverAtual());
    }

    public function test_it_discards_a_value_left_by_another_user(): void
    {
        // CPF vazio corta antes da consulta: o que se prova aqui é que o valor
        // de outro usuário não vaza, não o resultado da busca.
        $this->autenticar(id: 99, cpf: '');

        session([EgapSession::SESSION_KEY => ['user_id' => 77, 'egap_id' => 4676, 'info' => ['cargo' => 'JUIZ']]]);

        $this->assertNull(EgapSession::resolverAtual());
        $this->assertSame(
            ['user_id' => 99, 'egap_id' => null],
            session(EgapSession::SESSION_KEY),
            'o bloco inteiro do outro usuário tem de sair, não só o egap_id',
        );
    }

    public function test_it_reuses_the_cached_lotacao_while_the_switcher_agrees(): void
    {
        $this->autenticar(id: 99, cpf: '12345678909');

        session([
            EgapSession::SESSION_KEY_SETOR => 2933,
            EgapSession::SESSION_KEY => ['user_id' => 99, 'lotacao' => ['setor' => 2933, 'codigodauo' => 766]],
        ]);

        $this->assertSame(2933, EgapSession::setorAtual());
        $this->assertSame(766, EgapSession::unidadeAtual());
    }

    public function test_it_reuses_the_cached_info(): void
    {
        $this->autenticar(id: 99, cpf: '12345678909');

        $info = ['nome' => 'FULANO', 'cpf' => '12345678909', 'cargo' => 'ANALISTA JUDICIARIO'];
        session([EgapSession::SESSION_KEY => ['user_id' => 99, 'info' => $info]]);

        $this->assertSame($info, EgapSession::info());
        $this->assertSame('ANALISTA JUDICIARIO', EgapSession::cargoAtual());
    }

    public function test_it_resolves_to_null_for_a_visitor(): void
    {
        $this->assertNull(EgapSession::resolverAtual());
        $this->assertNull(EgapSession::cargoAtual());
        $this->assertNull(EgapSession::setorAtual());
        $this->assertNull(session(EgapSession::SESSION_KEY));
    }

    private function autenticar(int $id, string $cpf): void
    {
        $user = new User(['cpf' => $cpf]);
        $user->id = $id;

        Auth::guard(User::GUARD)->setUser($user);
    }
}
