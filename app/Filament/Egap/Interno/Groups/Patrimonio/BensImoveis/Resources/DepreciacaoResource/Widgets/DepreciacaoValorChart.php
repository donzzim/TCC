<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource\Widgets;

use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Reactive;

class DepreciacaoValorChart extends ChartWidget
{
    #[Reactive]
    public ?string $imovelId = null;

    protected ?string $heading = 'Depreciação ao Longo do Tempo';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '220px';

    protected static bool $isLazy = false;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        if (blank($this->imovelId)) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $registros = Depreciacao::query()
            ->where('Id_imovel', $this->imovelId)
            ->where('data_calculo', '<=', Carbon::now())
            ->orderBy('data_calculo')
            ->get(['data_calculo', 'valor_liquido_contabil']);

        [$labels, $data] = $this->dedupeConsecutiveValores($registros);

        return [
            'datasets' => [
                [
                    'label' => 'Valor Líquido Contábil',
                    'data' => $data,
                    'borderColor' => 'rgba(245, 158, 11, 1)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @param  Collection<int, Depreciacao>  $registros
     * @return array{0: array<string>, 1: array<float>}
     */
    protected function dedupeConsecutiveValores($registros): array
    {
        $labels = [];
        $data = [];
        $valorAnterior = null;

        foreach ($registros as $registro) {
            $valor = (float) $registro->valor_liquido_contabil;

            if ($valorAnterior !== null && $valor === $valorAnterior) {
                continue;
            }

            $labels[] = Carbon::parse($registro->data_calculo)->format('d/m/Y');
            $data[] = $valor;
            $valorAnterior = $valor;
        }

        return [$labels, $data];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    public function isEmpty(): bool
    {
        return blank($this->imovelId) || parent::isEmpty();
    }

    public function getEmptyStateHeading(): string|Htmlable
    {
        return blank($this->imovelId)
            ? 'Selecione um imóvel no filtro'
            : 'Nenhuma depreciação encontrada';
    }

    public function getEmptyStateDescription(): string|Htmlable|null
    {
        return blank($this->imovelId)
            ? 'O gráfico é exibido após a busca por um imóvel na tabela.'
            : 'Não há registros de depreciação para o imóvel selecionado.';
    }
}
