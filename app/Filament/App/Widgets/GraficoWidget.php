<?php

namespace App\Filament\App\Widgets;

use App\Models\Escola\CursoAtividades;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class GraficoWidget extends ChartWidget
{
    protected ?string $heading = 'Carga Horária Mensal';

    protected ?string $description = 'Horas de atividades por mês';

    protected static ?int $sort = 2;

    protected int|string|array $columns = 2;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $user = User::findOrFail(Filament::auth()->id());
        $meses = [];
        $horas = [];

        // Últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $data = now()->subMonths($i);
            $meses[] = $data->format('M/Y');

            $query = CursoAtividades::query()
                ->whereYear('inicio', $data->year)
                ->whereMonth('inicio', $data->month);

            // Filtrar por perfil
            if ($user->hasRole('painel_externo')) {
                $query->whereHas('curso.instrutores', function (Builder $q) use ($user) {
                    $q->where('instrutor_id', $user->id);
                });
            } else {
                $query->whereHas('curso.alunos', function (Builder $q) use ($user) {
                    $q->where('aluno_id', $user->id);
                });
            }

            // Calcular horas baseado na duração das atividades
            $atividades = $query->get();
            $totalHoras = 0;

            foreach ($atividades as $atividade) {
                if ($atividade->inicio && $atividade->termino) {
                    $totalHoras += Carbon::parse($atividade->inicio)->diffInHours($atividade->termino);
                }
            }

            $horas[] = $totalHoras;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Horas',
                    'data' => $horas,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $meses,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "h"; }',
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
