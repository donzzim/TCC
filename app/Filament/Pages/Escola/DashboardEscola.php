<?php

namespace App\Filament\Pages\Escola;

use UnitEnum;
use BackedEnum;
use App\Filament\Widgets\Escola\DashboardCursosPorAreaChart;
use App\Filament\Widgets\Escola\DashboardCursosPorModalidadeChart;
use App\Filament\Widgets\Escola\DashboardIndicadoresMensaisChart;
use App\Filament\Widgets\Escola\DashboardIndicadoresPedagogicosStats;
use App\Filament\Widgets\Escola\DashboardStatsEscola;
use App\Filament\Widgets\Escola\DashboardTipoPessoaTable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Support\Colors\Color;

class DashboardEscola extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $routePath = 'dashboard-escola';
    protected static ?string $title = 'Dashboard - Projeto Pedagógico';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 1;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filtros')
                    ->description('Refina os indicadores pedagogicos por intervalo de realizacao dos cursos.')
                    ->schema([
                        DatePicker::make('inicio')
                            ->label('Data inicial')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()->startOfYear()->toDateString())
                            ->live(),

                        DatePicker::make('termino')
                            ->label('Data final')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()->toDateString())
                            ->live(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearFilters')
                ->label('Limpar filtros')
                ->icon('heroicon-m-x-mark')
                ->color('gray')
                ->visible(fn (): bool => filled($this->filters))
                ->action(function (): void {
                    $this->filters = [];
                    $this->getFiltersForm()->fill();
                }),
        ];
    }

    public static function rgbToHex($r, $g, $b)
    {
        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));

        $hexR = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $hexG = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $hexB = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#' . $hexR . $hexG . $hexB;
    }
    public static function getColors($color)
    {
        $return = [];
        $arr = [500, 400, 600, 300, 700, 200, 800, 100, 900, 50];
        foreach ($arr as $value) {
            $return[] = Color::convertToRgb((string) $color[$value]);
        }

        return $return;
    }
    public function getColumns(): int | array
    {
        return 8;
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }
    public function getWidgets(): array
    {
        return [
            DashboardStatsEscola::class,
            DashboardIndicadoresPedagogicosStats::class,
            DashboardCursosPorAreaChart::class,
            DashboardCursosPorModalidadeChart::class,
            DashboardIndicadoresMensaisChart::class,
            DashboardTipoPessoaTable::class,
        ];
    }
}
