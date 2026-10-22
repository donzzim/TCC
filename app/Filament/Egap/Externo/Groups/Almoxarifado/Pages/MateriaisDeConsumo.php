<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Pages;

use App\Filament\Egap\Externo\Clusters\ExternoCluster;

class MateriaisDeConsumo extends RequisicaoDeMateriaisPage
{
    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = ExternoCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox';

    protected static string | \UnitEnum | null $navigationGroup = 'Almoxarifado';

    protected static ?string $slug = 'almoxarifado/requisicao-de-materiais-de-consumo';

    protected static ?string $navigationLabel = 'Materiais de Consumo';

    protected static ?string $title = 'Requisição de Materiais de Consumo';

    protected string $view = 'egap.filament.pages.externo.almoxarifado.materiais-de-consumo';

    public function tipoMaterial(): string
    {
        return 'C';
    }
}
