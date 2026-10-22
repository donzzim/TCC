<?php

namespace App\Filament\Egap\Interno\Clusters;

use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioAccess;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class PatrimonioCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Patrimônio';

    protected static ?string $clusterBreadcrumb = 'Patrimônio';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function canAccess(): bool
    {
        return PatrimonioAccess::allowed();
    }
}
