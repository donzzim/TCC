<?php

namespace App\Filament\Clusters\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Clusters\Cluster;

class OrcamentoCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Orçamento';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Orçamentário';
    protected static ?int $navigationSort = 3;
}
