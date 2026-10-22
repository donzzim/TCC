<?php

namespace App\Filament\Egap\Support;

use App\Filament\Egap\Auth\EgapSession;
use App\Models\egap\UserEgap;
use App\Services\Egap\Mobile\ConferenciaBensService;

/**
 * Setor e unidade judiciária em vigor nas telas do Ambiente Externo.
 *
 * Vêm da lotação do usuário autenticado ({@see EgapSession}), alimentada por
 * `user_lotacao` e trocável pelo seletor do topo. A lotação legada em
 * `egap_mat_lotacao` sobrou como último recurso para quem ainda não tem
 * `user_lotacao`.
 *
 * Até 2026-09 havia um modal que pedia unidade e setor à mão a cada sessão, e
 * a escolha ficava numa chave própria com precedência sobre tudo. O modal saiu
 * junto com o trait `SelecionaSetorAtual`; a chave não é mais escrita por
 * ninguém, então a leitura dela também saiu.
 *
 * Conferido no banco: `egap_mat_lotacao.unidade_judiciaria` coincide com
 * `setores.codigodauo` em 99,3% de 328.130 linhas, e `user_lotacao` cobre 7.185
 * usuários contra 4.450 da tabela legada.
 */
class SetorSelecionado
{
    public static function resolverAtual(): ?int
    {
        return EgapSession::setorAtual()
            ?? UserEgap::currentAuthenticated()
                ?->lotacoes()
                ->first()
                ?->setor;
    }

    /**
     * Complementa {@see resolverAtual()} com a Unidade Judiciária correspondente,
     * necessária para reaproveitar serviços que operam por Unidade + Setor (ex.:
     * {@see ConferenciaBensService}, criado originalmente
     * para o app mobile).
     */
    public static function resolverUnidadeAtual(): ?int
    {
        return EgapSession::unidadeAtual()
            ?? UserEgap::currentAuthenticated()
                ?->lotacoes()
                ->first()
                ?->unidade_judiciaria;
    }
}
