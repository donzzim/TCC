<?php

namespace App\Filament\Egap\Externo\Auth;

use App\Models\egap\User;
use Filament\Pages\Page;

abstract class ExternoPage extends Page
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->acessaAreaExterna();
    }
}
