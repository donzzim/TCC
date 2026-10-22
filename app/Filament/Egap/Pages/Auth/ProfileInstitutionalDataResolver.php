<?php

namespace App\Filament\Egap\Pages\Auth;

use App\Models\egap\Admin\InfoUser;
use App\Models\egap\Admin\Lotacao;
use App\Models\egap\UserEgap;

class ProfileInstitutionalDataResolver
{
    /**
     * @param  array<int, string>  $roleNames
     * @return array<string, string>
     */
    public static function resolve(?UserEgap $userEgap, ?InfoUser $infoUser, ?Lotacao $lotacao, array $roleNames): array
    {
        return [
            'cargo' => $infoUser?->cargo ?: '-',
            'unidade_judiciaria' => $lotacao?->unidadeJudiciaria?->setor ?: '-',
            'setor' => $lotacao?->setorRef?->setor ?: '-',
            'bloqueado_legado' => $userEgap === null ? '-' : ($userEgap->block ? 'Sim' : 'Não'),
            'perfis_acesso' => $roleNames === [] ? 'Nenhum perfil atribuído' : implode(', ', $roleNames),
            'data_cadastro' => $userEgap?->registerDate?->format('d/m/Y') ?? '-',
            'ultimo_acesso' => $userEgap?->lastvisitDate?->format('d/m/Y H:i') ?? '-',
        ];
    }
}
