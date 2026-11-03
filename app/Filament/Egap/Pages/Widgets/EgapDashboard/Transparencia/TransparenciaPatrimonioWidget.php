<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia;

use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\BensPatrimoniaisBaixados;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\BensPatrimoniaisMovimentados;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\BensPermanentesMoveis;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\BensPermanentesMoveisPatrimonio;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\BensSolicitados;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\ExecucaoOrcamentaria;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\ExecucaoOrcamentariaPatrimonio;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\InventarioOnlineSituacaoContabil;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\InventarioOnlineSituacaoInventario;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia\Patrimonio\SituacaoBensPatrimoniaisEgap;

class TransparenciaPatrimonioWidget extends SeletorGraficosWidget
{
    // logo depois dos números gerais (PatrimonioOverviewStats = 1)
    protected static ?int $sort = 2;

    public function titulo(): string
    {
        return 'Seção de Patrimônio';
    }

    public function icone(): string
    {
        return 'heroicon-o-building-library';
    }

    public function prefixo(): string
    {
        return 'patrimonio';
    }

    protected function indicadores(): array
    {
        return [
            'bens_adquiridos' => 'Bens Adquiridos',
            'bens_adquiridos_patrimonio' => 'Bens Adquiridos - Patrimônio',
            'bens_movimentados' => 'Bens Movimentados',
            'bens_baixados' => 'Bens Baixados',
            'bens_patrimonio' => 'Bens Patrimônio',
            'pedido_bens' => 'Pedido de Bens - Patrimônio',
            'execucao_orcamentaria' => 'Execução Orçamentária',
            'execucao_orcamentaria_patrimonio' => 'Execução Orçamentária - Patrimônio',
            'inventario_contabil' => 'Inventário Online - Situação Contábil',
            'inventario_situacao' => 'Inventário Online - Situação do Inventário',
        ];
    }

    protected function graficos(): array
    {
        return [
            'bens_adquiridos' => BensPermanentesMoveis::class,
            'bens_adquiridos_patrimonio' => BensPermanentesMoveisPatrimonio::class,
            'bens_movimentados' => BensPatrimoniaisMovimentados::class,
            'bens_baixados' => BensPatrimoniaisBaixados::class,
            'bens_patrimonio' => SituacaoBensPatrimoniaisEgap::class,
            'pedido_bens' => BensSolicitados::class,
            'execucao_orcamentaria' => ExecucaoOrcamentaria::class,
            'execucao_orcamentaria_patrimonio' => ExecucaoOrcamentariaPatrimonio::class,
            'inventario_contabil' => InventarioOnlineSituacaoContabil::class,
            'inventario_situacao' => InventarioOnlineSituacaoInventario::class,
        ];
    }
}
