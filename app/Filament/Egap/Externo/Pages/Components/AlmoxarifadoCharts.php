<?php

namespace App\Filament\Egap\Externo\Pages\Components;

use App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado\ExecucaoOrcamentaria;
use App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado\ExecucaoOrcamentariaAlmoxarifado;
use App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado\MateriaisConsumo;
use App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado\MateriaisConsumoAlmoxarifado;
use App\Filament\Egap\Externo\Pages\Widgets\Almoxarifado\RequisicoesBensConsumoAtendidas;
use Illuminate\View\View;
use Livewire\Component;

class AlmoxarifadoCharts extends Component
{
    public string $selectedIndicator = 'materiais_fornecidos';

    public string $chartType = 'bar';

    public ?string $currentWidget = null;

    public int $widgetRenderKey = 0;

    public bool $hasGenerated = false;

    public string $sectionTitle = 'Seção de Almoxarifado';

    public string $sectionDescription = 'Selecione os filtros e clique em "Gerar Gráfico" para visualizar os dados.';

    public array $indicators = [
        'materiais_fornecidos' => 'Materiais de Consumo Fornecidos',
        'materiais_fornecidos_almoxarifado' => 'Materiais de Consumo Fornecidos - Almoxarifado',
        'requisicoes' => 'Requisições de Bens de Consumo',
        'execucao_orcamentaria' => 'Execução Orçamentária',
        'execucao_orcamentaria_almoxarifado' => 'Execução Orçamentária - Almoxarifado',
    ];

    protected array $widgets = [
        'materiais_fornecidos' => MateriaisConsumo::class,
        'materiais_fornecidos_almoxarifado' => MateriaisConsumoAlmoxarifado::class,
        'requisicoes' => RequisicoesBensConsumoAtendidas::class,
        'execucao_orcamentaria' => ExecucaoOrcamentaria::class,
        'execucao_orcamentaria_almoxarifado' => ExecucaoOrcamentariaAlmoxarifado::class,
    ];

    public function mount(): void
    {
        $this->generateChart();
    }

    public function generateChart(): void
    {
        $this->currentWidget = $this->widgets[$this->selectedIndicator]
            ?? MateriaisConsumo::class;

        $this->chartType = in_array($this->chartType, ['bar', 'line', 'bubble', 'doughnut', 'pie', 'polarArea'], true)
            ? $this->chartType
            : 'bar';

        $this->widgetRenderKey++;
        $this->hasGenerated = true;
    }

    public function render(): View
    {
        return view('egap.livewire.externo.portal-transparencia.almoxarifado-charts', [
            'indicators' => $this->indicators,
        ]);
    }
}
