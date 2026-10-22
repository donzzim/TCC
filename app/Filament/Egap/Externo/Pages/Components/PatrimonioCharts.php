<?php

namespace App\Filament\Egap\Externo\Pages\Components;

use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\BensPatrimoniaisBaixados;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\BensPatrimoniaisMovimentados;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\BensPermanentesMoveis;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\BensPermanentesMoveisPatrimonio;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\BensSolicitados;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\ExecucaoOrcamentaria;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\ExecucaoOrcamentariaPatrimonio;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\InventarioOnlineSituacaoContabil;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\InventarioOnlineSituacaoInventario;
use App\Filament\Egap\Externo\Pages\Widgets\Patrimonio\SituacaoBensPatrimoniaisEgap;
use Illuminate\View\View;
use Livewire\Component;

class PatrimonioCharts extends Component
{
    public string $selectedIndicator = 'bens_adquiridos';

    public string $chartType = 'bar';

    public ?string $currentWidget = null;

    public int $widgetRenderKey = 0;

    public bool $hasGenerated = false;

    public string $sectionTitle = 'Seção de Patrimônio';

    public string $sectionDescription = 'Selecione os filtros e clique em "Gerar Gráfico" para visualizar os dados.';

    public array $indicators = [
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

    protected array $widgets = [
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

    public function mount(): void
    {
        $this->generateChart();
    }

    public function generateChart(): void
    {
        $this->currentWidget = $this->widgets[$this->selectedIndicator]
            ?? BensPermanentesMoveis::class;

        $this->chartType = in_array($this->chartType, ['bar', 'line', 'bubble', 'doughnut', 'pie', 'polarArea'], true)
            ? $this->chartType
            : 'bar';

        $this->widgetRenderKey++;
        $this->hasGenerated = true;
    }

    public function render(): View
    {
        return view('egap.livewire.externo.portal-transparencia.patrimonio-charts', [
            'indicators' => $this->indicators,
        ]);
    }
}
