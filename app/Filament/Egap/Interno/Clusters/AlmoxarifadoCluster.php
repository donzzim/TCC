<?php

namespace App\Filament\Egap\Interno\Clusters;

use App\Filament\Egap\Interno\Permissions\Almoxarifado\AlmoxarifadoAccess;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class AlmoxarifadoCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Almoxarifado';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function canAccess(): bool
    {
        return AlmoxarifadoAccess::allowed();
    }
}
