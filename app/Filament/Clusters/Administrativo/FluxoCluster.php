<?php

namespace App\Filament\Clusters\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Clusters\Cluster;

class FluxoCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Fluxo de Trabalho';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 4;
}
