<?php

namespace App\Filament\Widgets\Contratacao;

use App\Filament\Pages\Contratacao\DashboardContratacao;
use App\Models\Contratacao\TipoProcesso;
use App\Models\Contratacao\TipoProcessoEvento;
use App\Models\Escola\AreaTematica;
use App\Models\Escola\Cursos;
use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardProcessoEventosPorTipoChart extends ChartWidget
{
    protected ?string $heading = 'Processo Eventos por Tipo';
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '100s';
    protected int|string|array $columnSpan = 4;
    // protected int|string|array $columnSpan = [
    //     'xs' => 1,
    //     'sm' => 1,
    //     'md' => 1,
    //     'xl' => 2,
    // ];
    // protected function getFilters(): ?array
    // {
    //     // Busca dinamicamente os anos em que há cursos cadastrados
    //     $anos = Cursos::query()
    //         ->select(DB::raw('YEAR(inicio) as ano'))
    //         ->distinct()
    //         ->orderBy('ano', 'desc')
    //         ->pluck('ano')
    //         ->mapWithKeys(fn($ano) => [$ano => $ano])
    //         ->all();

    //     return [
    //         Select::make('ano')
    //             ->label('Selecione o Ano')
    //             ->options($anos)
    //             ->default(date('Y')),
    //     ];
    // }

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return $this->heading;
    }

    protected function getData(): array
    {
        $anoSelecionado = $this->filters['ano'] ?? date('Y');

        $dados = TipoProcessoEvento::withSum([
            'eventos' => function (Builder $query) use ($anoSelecionado) {
                $query->whereYear('data', $anoSelecionado);
            }
        ], 'valor')->get();
        $labels = $dados->pluck('descricao')->toArray();
        $data = $dados->pluck('eventos_sum_valor')->toArray();

        return [
            'datasets' => [
                [
                    'axis' => 'y',
                    'label' => 'Processo Eventos',
                    'data' => $data,
                    'backgroundColor' => DashboardContratacao::getColors(Color::Orange),
                    // 'borderColor' => '#ffffff',
                    'borderWidth' => '0',
                ],
            ],
            'labels' => $labels,
            'options' => [
                'indexAxis' => 'y',
            ]
        ];
    }
    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Valor Total (R$)',
                        'font' => [
                            'weight' => 'bold',
                        ],
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
    protected function getType(): string
    {
        return 'bar';
    }
}
