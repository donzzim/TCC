<?php

namespace App\Filament\Egap\Interno\Permissions\Almoxarifado;

abstract class AlmoxarifadoPage
{
    public static function canAccess(): bool
    {
        return AlmoxarifadoAccess::allowed();
    }
}
