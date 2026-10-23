<?php

namespace Tests\Feature\MobileApi\Concerns;

use App\Filament\Egap\Auth\EgapSession;
use App\Filament\Egap\Auth\MobileEgapSession;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\User;

trait UsaSessaoMobile
{
    protected function temAcessoEgap(User $user): bool
    {
        return (int) $user->ativo === 1 && ($user->can('egap_access') || $user->isAdminEgap());
    }

    protected function usuarioComVariasLotacoes(): User
    {
        $candidatos = User::query()
            ->has('setores', '>', 1)
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($candidatos as $user) {
            if (filled(EgapSession::formatosDeBusca($user->cpf)) && $this->temAcessoEgap($user)) {
                return $user;
            }
        }

        $this->markTestSkipped('nenhum usuário com várias lotações e egap_access neste banco');
    }

    protected function usuarioSemUserLotacaoComLegado(): User
    {
        $candidatos = User::query()
            ->where('ativo', 1)
            ->whereNotNull('cpf')
            ->doesntHave('setores')
            ->orderBy('id')
            ->limit(500)
            ->get();

        foreach ($candidatos as $user) {
            $egapId = MobileEgapSession::egapIdPorCpf($user->cpf);

            if ($egapId && Lotacao::query()->where('id_user', $egapId)->exists()) {
                return $user;
            }
        }

        $this->markTestSkipped('nenhum usuário sem user_lotacao e com lotação legada neste banco');
    }

    /**
     * @return array{0: MobileEgapSession, 1: string}
     */
    protected function sessaoPara(User $user, ?string $dispositivo = null): array
    {
        $token = $user->createToken('mobile-app');

        return [MobileEgapSession::iniciar($user, $token->accessToken, $dispositivo), $token->plainTextToken];
    }
}
