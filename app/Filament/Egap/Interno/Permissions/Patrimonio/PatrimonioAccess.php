<?php

namespace App\Filament\Egap\Interno\Permissions\Patrimonio;

use App\Models\egap\User;

final class PatrimonioAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_patrimonio'));
    }
}
