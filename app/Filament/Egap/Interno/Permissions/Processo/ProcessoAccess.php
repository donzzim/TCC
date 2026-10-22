<?php

namespace App\Filament\Egap\Interno\Permissions\Processo;

use App\Models\egap\User;

final class ProcessoAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_processo'));
    }
}
