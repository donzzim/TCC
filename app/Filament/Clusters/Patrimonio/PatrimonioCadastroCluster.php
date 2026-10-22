<?php

namespace App\Filament\Clusters\Patrimonio;

use UnitEnum;
use BackedEnum;
use Filament\Clusters\Cluster;

class PatrimonioCadastroCluster extends Cluster
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $modelLabel = 'Cadastro ';
    protected static ?string $puralModelLabel = 'Cadastro ';
    protected static ?string $navigationLabel = 'Cadastro ';
    protected static string | UnitEnum | null $navigationGroup = 'Patrimônio';
    protected static ?int $navigationSort = 99;
}
