<?php

namespace App\Filament\Egap\Interno\Permissions\Agendamento;

use App\Models\egap\User;

final class AgendamentoAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_agendamento'));
    }
}
