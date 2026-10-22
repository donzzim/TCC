<?php

namespace App\Filament\Egap\Interno\Permissions\Patrimonio;

abstract class PatrimonioPage
{
    public static function canAccess(): bool
    {
        return PatrimonioAccess::allowed();
    }
}
