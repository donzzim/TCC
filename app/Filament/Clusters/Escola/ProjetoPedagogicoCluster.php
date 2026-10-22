<?php

namespace App\Filament\Clusters\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Clusters\Cluster;

class ProjetoPedagogicoCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Ações Pedagógicas';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 1;
}
