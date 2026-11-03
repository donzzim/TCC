<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia;

use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

/**
 * Cartão do dashboard com um seletor de indicador e de tipo de gráfico (antes ficava na
 * página "Portal Transparência" do ambiente externo). O gráfico escolhido é um ChartWidget
 * de Transparencia/{Patrimonio,Almoxarifado}, montado dentro do cartão.
 */
abstract class SeletorGraficosWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isLazy = false;

    protected string $view = 'egap.filament.widgets.transparencia-graficos';

    // lado a lado em telas largas (como no Portal), um embaixo do outro nas menores
    protected int | string | array $columnSpan = [
        'md' => 4,
        'xl' => 2,
    ];

    private const TIPOS = [
        'bar' => 'Barras',
        'line' => 'Linha',
        'bubble' => 'Bolhas',
        'doughnut' => 'Rosca',
        'pie' => 'Pizza',
        'polarArea' => 'Polar',
    ];

    public string $selectedIndicator = '';

    public string $chartType = 'bar';

    public ?string $currentWidget = null;

    public int $widgetRenderKey = 0;

    abstract public function titulo(): string;

    abstract public function icone(): string;

    /** Prefixo dos ids e das chaves do Livewire (dois cartões na mesma página). */
    abstract public function prefixo(): string;

    /** @return array<string, string> chave do indicador => rótulo */
    abstract protected function indicadores(): array;

    /** @return array<string, class-string> chave do indicador => ChartWidget */
    abstract protected function graficos(): array;

    public function mount(): void
    {
        $this->selectedIndicator = array_key_first($this->indicadores());
        $this->generateChart();
    }

    public function generateChart(): void
    {
        $graficos = $this->graficos();

        $this->currentWidget = $graficos[$this->selectedIndicator] ?? reset($graficos);
        $this->chartType = array_key_exists($this->chartType, self::TIPOS) ? $this->chartType : 'bar';
        $this->widgetRenderKey++;
    }

    public function indicatorForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedIndicator')
                    ->hiddenLabel()
                    ->id($this->prefixo().'-indicator')
                    ->prefixIcon('heroicon-o-presentation-chart-line')
                    ->options($this->indicadores())
                    ->selectablePlaceholder(false)
                    ->native(false),
            ]);
    }

    public function chartTypeForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('chartType')
                    ->hiddenLabel()
                    ->prefixIcon('heroicon-o-adjustments-horizontal')
                    ->options(self::TIPOS)
                    ->selectablePlaceholder(false)
                    ->native(false),
            ]);
    }
}
