<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Support;

use App\Filament\Egap\Support\SetorSelecionado;
use App\Filament\Egap\Support\UsuarioSelecionado;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;

/**
 * Resolve o contexto (usuário + setor) das telas de Plano de Contratação do
 * Ambiente Externo, reaproveitando o mesmo mecanismo provisório de identificação
 * usado nos demais grupos ({@see UsuarioSelecionado} / {@see SetorSelecionado}).
 *
 * Substitui os `$_SESSION['codigousuario']` / `$_SESSION['codigosetor']` /
 * `$_SESSION['setor']` do legado (`pla_novasolicitacao.php`,
 * `pla_planodecontratacao.api.php`).
 */
class PlanoContratacaoContexto
{
    public static function usuarioId(): ?int
    {
        return UsuarioSelecionado::resolverAtual();
    }

    public static function usuario(): ?UserEgap
    {
        $id = self::usuarioId();

        return filled($id) ? UserEgap::query()->find($id) : null;
    }

    public static function usuarioNome(): ?string
    {
        return self::usuario()?->name;
    }

    public static function setorId(): ?int
    {
        return SetorSelecionado::resolverAtual();
    }

    public static function setor(): ?Setores
    {
        $id = self::setorId();

        return filled($id) ? Setores::query()->find($id) : null;
    }

    public static function setorNome(): ?string
    {
        return self::setor()?->Setor;
    }

    /**
     * Setores considerados na listagem "Meus Planos" — o setor atual e,
     * defensivamente, seus filhos diretos (o legado mantinha `arvoreSetores()`
     * recursivo, mas na prática usava só o setor corrente).
     *
     * @return array<int, int>
     */
    public static function setoresElegiveis(): array
    {
        $setorId = self::setorId();

        if (blank($setorId)) {
            return [];
        }

        $filhos = Setores::query()
            ->where('codigopai', $setorId)
            ->pluck('id')
            ->all();

        return array_values(array_unique([(int) $setorId, ...array_map('intval', $filhos)]));
    }

    /**
     * Ano de execução padrão do PCA — o legado grava `date('Y') + 1` na criação
     * de novas solicitações.
     */
    public static function anoExecucaoPadrao(): int
    {
        return (int) date('Y') + 1;
    }
}
