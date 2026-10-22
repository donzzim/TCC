<?php

namespace App\Filament\Egap\Interno\Clusters;

use App\Models\egap\User;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class PedidosCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdminEgap();
    }
}
