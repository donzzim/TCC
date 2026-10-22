<?php

namespace App\Filament\Egap\Interno\Clusters;

use App\Models\egap\User;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class AdminEgapCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Administração';
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $clusterBreadcrumb = 'Administração';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdminEgap();
    }
}
