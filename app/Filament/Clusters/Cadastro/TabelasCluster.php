<?php

namespace App\Filament\Clusters\Cadastro;

use UnitEnum;
use BackedEnum;
use Filament\Clusters\Cluster;

class TabelasCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Tabelas';
    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 10;
}
