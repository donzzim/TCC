<?php

namespace App\Filament\Egap\Support;

use App\Filament\Egap\Auth\EgapSession;

/**
 * Usuário legado (`egap_jos_users.id`) em vigor nas telas do Ambiente Externo.
 *
 * Resolve pelo CPF do usuário autenticado ({@see EgapSession}).
 *
 * Até 2026-09 havia um modal (`SelecionaUsuarioAtual`) que deixava escolher
 * *qualquer* usuário do EGAP numa lista de 10 mil nomes, e a escolha tinha
 * precedência sobre a identidade de quem estava logado. Além de provisório,
 * era um buraco: dava para ver os registros de outra pessoa. Saiu junto com o
 * trait.
 *
 * Quem não tem cadastro legado recebe `null` — e as telas mostram vazio, que é
 * a resposta correta para quem não tem histórico no EGAP.
 */
class UsuarioSelecionado
{
    public static function resolverAtual(): ?int
    {
        return EgapSession::resolverAtual();
    }
}
