<?php

namespace App\Filament\Egap\Interno\Permissions\Pedidos;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class PedidoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return PedidosAccess::allowed();
    }

    public static function canCreate(): bool
    {
        return PedidosAccess::allowed();
    }

    public static function canView(Model $record): bool
    {
        return PedidosAccess::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return PedidosAccess::allowed();
    }
}
