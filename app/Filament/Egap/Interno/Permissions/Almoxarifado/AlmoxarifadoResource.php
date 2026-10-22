<?php

namespace App\Filament\Egap\Interno\Permissions\Almoxarifado;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class AlmoxarifadoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return AlmoxarifadoAccess::allowed();
    }

    public static function canCreate(): bool
    {
        return AlmoxarifadoAccess::allowed();
    }

    public static function canView(Model $record): bool
    {
        return AlmoxarifadoAccess::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return AlmoxarifadoAccess::allowed();
    }
}
