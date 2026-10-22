<?php

namespace App\Filament\Widgets\Escola;

use App\Filament\Pages\Escola\DashboardEscola;
use App\Models\Escola\AreaTematica;
use App\Services\Pedagogico\DashboardPedagogicoService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class DashboardCursosPorAreaChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Cursos por Área Temática';
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '100s';
    protected int|string|array $columnSpan = 2;
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
        $service = app(DashboardPedagogicoService::class);
        [$inicio, $termino] = $service->periodo($this->filters);

        $dados = AreaTematica::withCount([
            'cursos' => function (Builder $query) use ($service, $inicio, $termino) {
                $service->aplicarPeriodoCursos($query, $inicio, $termino);
            }
        ])->get();

        $labels = $dados->pluck('descricao')->toArray();
        $data = $dados->pluck('cursos_count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Cursos',
                    'data' => $data,
                    'backgroundColor' => DashboardEscola::getColors(Color::Sky),
                    // 'borderColor' => '#ffffff',
                    'borderWidth' => '0',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
