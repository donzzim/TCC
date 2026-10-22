<?php

namespace App\Filament\Egap\Interno\Permissions\Processo;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class ProcessoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return ProcessoAccess::allowed();
    }

    public static function canCreate(): bool
    {
        return ProcessoAccess::allowed();
    }

    public static function canView(Model $record): bool
    {
        return ProcessoAccess::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return ProcessoAccess::allowed();
    }
}
