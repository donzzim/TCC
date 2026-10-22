<?php

namespace App\Filament\Egap\Interno\Permissions\Admin;

use App\Models\egap\User;

final class AdminAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_admin'));
    }
}
