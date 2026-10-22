<?php

namespace App\Filament\Widgets\Escola;

use App\Filament\Pages\Escola\DashboardEscola;
use App\Models\Escola\Modalidade;
use App\Services\Pedagogico\DashboardPedagogicoService;
use Filament\Support\Colors\Color;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class DashboardCursosPorModalidadeChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Cursos por Modalidade';
    protected static ?int $sort = 3;
    protected ?string $pollingInterval = '100s';
    protected int|string|array $columnSpan = 2;

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return $this->heading;
    }

    protected function getData(): array
    {
        $service = app(DashboardPedagogicoService::class);
        [$inicio, $termino] = $service->periodo($this->filters);

        $dados = Modalidade::withCount([
            'cursos' => function (Builder $query) use ($service, $inicio, $termino) {
                $service->aplicarPeriodoCursos($query, $inicio, $termino);
            }
        ])->get();

        $labels = $dados->pluck('descricao')->toArray();
        $data = $dados->pluck('cursos_count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Modalidade',
                    'data' => $data,
                    'backgroundColor' => DashboardEscola::getColors(Color::Green),
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
