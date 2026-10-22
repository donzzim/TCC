<?php

namespace App\Filament\Egap\Interno\Permissions\Patrimonio;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class PatrimonioResource extends Resource
{
    public static function canViewAny(): bool
    {
        return PatrimonioAccess::allowed();
    }

    public static function canCreate(): bool
    {
        return PatrimonioAccess::allowed();
    }

    public static function canView(Model $record): bool
    {
        return PatrimonioAccess::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return PatrimonioAccess::allowed();
    }
}
