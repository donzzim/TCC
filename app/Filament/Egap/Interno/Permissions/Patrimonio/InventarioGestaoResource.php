<?php

namespace App\Filament\Egap\Interno\Permissions\Patrimonio;

use Illuminate\Database\Eloquent\Model;

/**
 * Recursos de abertura do inventário: `egap_patrimonio` só consulta e
 * criar/editar/excluir exige {@see InventarioAccess::podeAbrir()}.
 */
abstract class InventarioGestaoResource extends PatrimonioResource
{
    public static function canViewAny(): bool
    {
        return InventarioAccess::podeVer();
    }

    public static function canView(Model $record): bool
    {
        return InventarioAccess::podeVer();
    }

    public static function canCreate(): bool
    {
        return InventarioAccess::podeAbrir();
    }

    public static function canEdit(Model $record): bool
    {
        return InventarioAccess::podeAbrir();
    }

    public static function canDelete(Model $record): bool
    {
        return InventarioAccess::podeAbrir();
    }

    public static function canDeleteAny(): bool
    {
        return InventarioAccess::podeAbrir();
    }
}
