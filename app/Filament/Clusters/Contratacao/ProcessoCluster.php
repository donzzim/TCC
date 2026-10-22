<?php

namespace App\Filament\Clusters\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Clusters\Cluster;

class ProcessoCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Processos';
    protected static string | UnitEnum | null $navigationGroup = 'Contratação';
    protected static ?int $navigationSort = 3;
}
