<?php

namespace App\Filament\Egap\Interno\Permissions\Pedidos;

use Filament\Pages\Page;

abstract class PedidoPage extends Page
{
    public static function canAccess(): bool
    {
        return PedidosAccess::allowed();
    }
}
