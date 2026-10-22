<?php

namespace App\Filament\Egap\Interno\Permissions\Cadastro;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class CadastroResource extends Resource
{
    public static function canViewAny(): bool
    {
        return CadastroAccess::allowed();
    }

    public static function canCreate(): bool
    {
        return CadastroAccess::allowed();
    }

    public static function canView(Model $record): bool
    {
        return CadastroAccess::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return CadastroAccess::allowed();
    }
}
