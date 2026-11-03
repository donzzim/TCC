<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia;

use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Almoxarifado\ExecucaoOrcamentaria;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Almoxarifado\ExecucaoOrcamentariaAlmoxarifado;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Almoxarifado\MateriaisConsumo;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Almoxarifado\MateriaisConsumoAlmoxarifado;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Almoxarifado\RequisicoesBensConsumoAtendidas;

class TransparenciaAlmoxarifadoWidget extends SeletorGraficosWidget
{
    // ao lado do de Patrimônio, antes dos demais gráficos
    protected static ?int $sort = 3;

    public function titulo(): string
    {
        return 'Seção de Almoxarifado';
    }

    public function icone(): string
    {
        return 'heroicon-o-archive-box';
    }

    public function prefixo(): string
    {
        return 'almoxarifado';
    }

    protected function indicadores(): array
    {
        return [
            'materiais_fornecidos' => 'Materiais de Consumo Fornecidos',
            'materiais_fornecidos_almoxarifado' => 'Materiais de Consumo Fornecidos - Almoxarifado',
            'requisicoes' => 'Requisições de Bens de Consumo',
            'execucao_orcamentaria' => 'Execução Orçamentária',
            'execucao_orcamentaria_almoxarifado' => 'Execução Orçamentária - Almoxarifado',
        ];
    }

    protected function graficos(): array
    {
        return [
            'materiais_fornecidos' => MateriaisConsumo::class,
            'materiais_fornecidos_almoxarifado' => MateriaisConsumoAlmoxarifado::class,
            'requisicoes' => RequisicoesBensConsumoAtendidas::class,
            'execucao_orcamentaria' => ExecucaoOrcamentaria::class,
            'execucao_orcamentaria_almoxarifado' => ExecucaoOrcamentariaAlmoxarifado::class,
        ];
    }
}
