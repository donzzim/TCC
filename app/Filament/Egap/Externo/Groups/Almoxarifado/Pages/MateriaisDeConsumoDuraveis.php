<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Pages;

use App\Filament\Egap\Externo\Clusters\ExternoCluster;

class MateriaisDeConsumoDuraveis extends RequisicaoDeMateriaisPage
{
    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = ExternoCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Almoxarifado';

    protected static ?string $slug = 'almoxarifado/requisicao-de-materiais-de-consumo-duraveis';

    protected static ?string $navigationLabel = 'Materiais de Cons. Duráveis';

    protected static ?string $title = 'Requisição de Materiais de Consumo Duráveis';

    protected string $view = 'egap.filament.pages.externo.almoxarifado.materiais-de-consumo';

    public function tipoMaterial(): string
    {
        return 'D';
    }
}
