<?php

namespace App\Filament\Egap\Externo\Clusters;

use App\Models\egap\User;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class ExternoCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-window';
    protected static ?string $navigationLabel = 'Ambiente Externo';
    protected static ?string $clusterBreadcrumb = 'Ambiente Externo';
    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->acessaAreaExterna();
    }
}
