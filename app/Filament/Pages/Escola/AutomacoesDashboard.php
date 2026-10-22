<?php

namespace App\Filament\Pages\Escola;

use App\Filament\Widgets\Escola\AutoDashboardCoursesTable;
use App\Filament\Widgets\Escola\AutoDashboardPendingChart;
use App\Filament\Widgets\Escola\AutoDashboardStats;
use App\Filament\Widgets\Escola\AutoDashboardStatusChart;
use App\Filament\Widgets\Escola\AutoDashboardStepStatusChart;
use App\Services\Auto\AutoDashboardService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class AutomacoesDashboard extends Dashboard
{
    use HasFiltersForm;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $routePath = 'dashboard-automacoes';
    protected static ?string $title = 'Dashboard de automações';
    protected static ?string $navigationLabel = 'Automações';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 2;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filtros da automação')
                    ->description('Acompanhe cursos por estado operacional, pendências e etapas prontas.')
                    ->schema([
                        Select::make('status')
                            ->label('Estados')
                            ->options(AutoDashboardService::statusOptions())
                            ->multiple()
                            ->searchable()
                            ->live(),

                        Select::make('attention')
                            ->label('Foco operacional')
                            ->options(AutoDashboardService::attentionOptions())
                            ->default('all')
                            ->native(false)
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
                ->visible(fn(): bool => filled($this->filters))
                ->action(function (): void {
                    $this->filters = [];
                    $this->getFiltersForm()->fill();
                }),
        ];
    }

    public function getColumns(): int|array
    {
        return 12;
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 12;
    }

    public function getWidgets(): array
    {
        return [
            AutoDashboardStats::class,
            AutoDashboardStatusChart::class,
            AutoDashboardStepStatusChart::class,
            AutoDashboardPendingChart::class,
            AutoDashboardCoursesTable::class,
        ];
    }
}
