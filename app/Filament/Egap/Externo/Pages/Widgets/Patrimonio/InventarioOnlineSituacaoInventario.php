<?php

namespace App\Filament\Egap\Externo\Pages\Widgets\Patrimonio;

use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;

class InventarioOnlineSituacaoInventario extends BaseChart
{
    protected ?string $heading = 'Acompanhamento do Invetário Online Anual - Situação do Inventário';

    protected function getData(): array
    {
        $bens = BemMovel::query()
            ->whereIn('SituacaoBem', [1, 7])
            ->get(['sit_inventario', 'SituacaoBem']);

        $normalizar = fn(mixed $value): string => mb_strtoupper(trim((string) $value), 'UTF-8');

        $dados = (object) [
            'localizados' => $bens->filter(function (BemMovel $bem) use ($normalizar): bool {
                $situacao = $normalizar($bem->sit_inventario);

                return $situacao === '' || str_starts_with($situacao, 'LOCALIZADO');
            })->count(),
            'nao_localizados' => $bens->filter(fn(BemMovel $bem): bool => str_starts_with($normalizar($bem->sit_inventario), 'N'))->count(),
            'a_inventariar' => $bens->filter(fn(BemMovel $bem): bool => str_starts_with($normalizar($bem->sit_inventario), 'A INVENTARIAR'))->count(),
            'em_transferencia' => $bens->filter(fn(BemMovel $bem): bool => str_starts_with($normalizar($bem->sit_inventario), 'EM TRANSF'))->count(),
        ];

        $labels = [
            'Localizados',
            'Não localizados',
            'A inventariar',
            'Em transferência',
        ];

        $quantidades = [
            (int) $dados->localizados,
            (int) $dados->nao_localizados,
            (int) $dados->a_inventariar,
            (int) $dados->em_transferencia,
        ];

        $colors = $this->getColors(count($quantidades));
        $border_colors = $this->getBorderColors(count($quantidades));

        if ($this->chartType === 'bubble') {
            return [
                'datasets' => [
                    [
                        'label' => 'Quantidade',
                        'data' => collect($quantidades)->map(function ($valor, $index) {
                            $qtde = max((int) $valor, 1);

                            return [
                                'x' => $index + 1,
                                'y' => $qtde,
                                'r' => max(5, min(25, (int) round(sqrt($qtde) * 1.5))),
                            ];
                        })->toArray(),
                        'backgroundColor' => $colors,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        if (in_array($this->chartType, ['pie', 'doughnut', 'polarArea'], true)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Quantidade',
                        'data' => $quantidades,
                        'backgroundColor' => $colors,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        if ($this->chartType === 'line') {
            return [
                'datasets' => [
                    [
                        'label' => 'Quantidade',
                        'data' => $quantidades,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                        'borderColor' => 'rgba(59, 130, 246, 1)',
                        'pointBackgroundColor' => $colors,
                        'tension' => 0.3,
                        'fill' => true,
                    ],
                ],
                'labels' => $labels,
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade',
                    'data' => $quantidades,
                    'backgroundColor' => $colors,
                    'borderColor' => $border_colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
