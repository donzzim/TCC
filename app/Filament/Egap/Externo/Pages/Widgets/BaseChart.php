<?php

namespace App\Filament\Egap\Externo\Pages\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class BaseChart extends ChartWidget
{
    public ?string $chartType = 'bar';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        $allowedTypes = [
            'bar',
            'line',
            'bubble',
            'doughnut',
            'pie',
            'polarArea',
        ];

        return in_array($this->chartType, $allowedTypes, true)
            ? $this->chartType
            : 'bar';
    }

    /**
     * Agrupa e agrega por ano direto no banco (GROUP BY YEAR(coluna)). Essas
     * tabelas legadas tem dezenas de milhares de linhas; fazer isso hidratando
     * cada registro como model Eloquent e somando em PHP (como era antes)
     * deixava a request presa por segundos so no loop, mesmo com o SQL rapido.
     *
     * @param  array<string, string>  $aggregates  alias => expressao SQL agregada, ex.: ['qtde' => 'COUNT(*)', 'valor' => 'SUM(ValorAquisicao)']
     * @return Collection<int, object>
     */
    protected function aggregateByYear(Builder $query, string $dateColumn, array $aggregates): Collection
    {
        $selects = collect($aggregates)
            ->map(fn (string $expression, string $alias): string => "{$expression} as {$alias}")
            ->implode(', ');

        return $query
            ->whereNotNull($dateColumn)
            ->where($dateColumn, '<>', '0000-00-00 00:00:00')
            ->selectRaw("YEAR({$dateColumn}) as ano, {$selects}")
            ->groupBy('ano')
            ->orderBy('ano')
            ->get();
    }

    protected function getColors(int $count): array
    {
        $colors = [
            'rgba(59, 130, 246, 0.5)',
            'rgba(16, 185, 129, 0.5)',
            'rgba(245, 158, 11, 0.5)',
            'rgba(239, 68, 68, 0.5)',
            'rgba(139, 92, 246, 0.5)',
            'rgba(236, 72, 153, 0.5)',
            'rgba(6, 182, 212, 0.5)',
            'rgba(132, 204, 22, 0.5)',
        ];

        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = $colors[$i % count($colors)];
        }

        return $result;
    }

    protected function getBorderColors(int $count): array
    {
        $border_colors = [
            'rgba(59, 130, 246, 1)',
            'rgba(16, 185, 129, 1)',
            'rgba(245, 158, 11, 1)',
            'rgba(239, 68, 68, 1)',
            'rgba(139, 92, 246, 1)',
            'rgba(236, 72, 153, 1)',
            'rgba(6, 182, 212, 1)',
            'rgba(132, 204, 22, 1)',
        ];

        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $result[] = $border_colors[$i % count($border_colors)];
        }

        return $result;
    }
}
