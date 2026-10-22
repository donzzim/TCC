<?php

namespace App\Filament\Egap\Pages;

use App\Filament\Egap\Pages\Widgets\EgapDashboard\PatrimonioImoveisPorContaChart;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\PatrimonioMoveisPorAnoChart;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\PatrimonioMoveisPorSituacaoChart;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\PatrimonioOverviewStats;
use App\Filament\Egap\Pages\Widgets\EgapDashboard\PatrimonioTopMateriaisValorTable;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class EgapDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Painel Administrativo do E-Gap';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    public function getWidgets(): array
    {
        return [
            PatrimonioOverviewStats::class,
            PatrimonioMoveisPorSituacaoChart::class,
            PatrimonioMoveisPorAnoChart::class,
            PatrimonioImoveisPorContaChart::class,
            PatrimonioTopMateriaisValorTable::class,
        ];
    }

    public static function getColors(array $color, int $limit = 10): array
    {
        $palette = [900, 800, 700, 600, 500, 400, 300, 200, 100, 50];
        $colors = [];

        while (count($colors) < max($limit, 1)) {
            foreach ($palette as $tone) {
                $colors[] = $color[$tone];

                if (count($colors) >= max($limit, 1)) {
                    break;
                }
            }
        }

        return $colors;
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 4,
            'xl' => 4,
        ];
    }

    public function mount(): void
    {
        if (session()->has('egap_boas_vindas')) {
            $firstName = str(auth()->user()->name)->trim()->before(' ')->toString();

            Notification::make()
                ->title('Bem-vindo ao EGAP, ' . $firstName . '!')
                ->body('Login realizado com sucesso.')
                ->success()
                ->send();

            session()->forget('egap_boas_vindas');
        }
    }
}
