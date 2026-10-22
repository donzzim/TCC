<?php

namespace App\Filament\Egap\Interno\Permissions\Almoxarifado;

use App\Models\egap\User;

final class AlmoxarifadoAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_almoxarifado'));
    }
}
