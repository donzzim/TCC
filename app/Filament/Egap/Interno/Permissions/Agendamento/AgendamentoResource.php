<?php

namespace App\Filament\Egap\Interno\Permissions\Agendamento;

use App\Filament\Egap\Interno\Permissions\Pedidos\PedidosAccess;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

abstract class AgendamentoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return AgendamentoAccess::allowed();
    }

    public static function canCreate(): bool
    {
        return AgendamentoAccess::allowed();
    }

    public static function canView(Model $record): bool
    {
        return AgendamentoAccess::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return AgendamentoAccess::allowed();
    }
}
