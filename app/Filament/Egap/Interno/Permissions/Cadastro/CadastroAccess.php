<?php

namespace App\Filament\Egap\Interno\Permissions\Cadastro;

use App\Models\egap\User;

final class CadastroAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_cadastro'));
    }
}
