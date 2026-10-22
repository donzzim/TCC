<?php

namespace App\Filament\Egap\Interno\Permissions\Pedidos;

use App\Models\egap\User;

final class PedidosAccess
{
    public static function allowed(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isAdminEgap() || $user->hasRole('egap_pedido'));
    }
}
