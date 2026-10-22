<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Administrativo\EventoCalendarWidget;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class Home extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Home';

    public function getHeaderWidgetsColumns(): int|array
    {
        return 6;
    }

    public function renderBadge(string $title, string $color = 'text-blue-800', string $bgcolor = 'bg-blue-100'): string
    {
        return "<span class=\"inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-medium {$bgcolor} {$color}\">{$title}</span>";
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filtro')
                    ->description(function (): HtmlString {
                        $inicio = filled($this->filters['inicio'] ?? null)
                            ? Carbon::parse($this->filters['inicio'])->format('d/m/Y')
                            : date('d/m/Y');
                        $termino = filled($this->filters['termino'] ?? null)
                            ? Carbon::parse($this->filters['termino'])->format('d/m/Y')
                            : date('d/m/Y');

                        $badges = [
                            $this->renderBadge("Período: {$inicio} a {$termino}", 'text-slate-800', 'bg-slate-200'),
                        ];

                        if (filled($this->filters['feriados'] ?? false)) {
                            $badges[] = $this->renderBadge('Feriados', 'text-gray-50', 'bg-gray-500');
                        }
                        if (filled($this->filters['cursos'] ?? false)) {
                            $badges[] = $this->renderBadge('Cursos', 'text-emerald-50', 'bg-emerald-600');
                        }
                        if (filled($this->filters['registros'] ?? false)) {
                            $badges[] = $this->renderBadge('Registros Funcionais', 'text-slate-50', 'bg-blue-500');
                        }

                        return new HtmlString(
                            '<div class="flex flex-wrap items-center gap-2">' . implode('', $badges) . '</div>'
                        );
                    })
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                DatePicker::make('inicio')
                                    ->label('Período: início')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state): void {
                                        $this->dispatch('toggleAgenda');
                                    })
                                    ->format('d/m/Y')
                                    ->default(date('Y-m-') . '01'),
                                DatePicker::make('termino')
                                    ->label('Período: término')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state): void {
                                        $this->dispatch('toggleAgenda');
                                    })
                                    ->format('d/m/Y')
                                    ->default(date('Y-m-d')),
                            ])
                            ->columnSpanFull(),
                        Grid::make(['default' => 1, 'md' => 3])
                            ->schema([
                                Toggle::make('feriados')
                                    ->reactive()
                                    ->label('Feriados')
                                    ->inline(false)
                                    ->afterStateUpdated(function ($state): void {
                                        $this->dispatch('toggleAgenda');
                                    }),
                                Toggle::make('cursos')
                                    ->reactive()
                                    ->label('Cursos')
                                    ->inline(false)
                                    ->afterStateUpdated(function ($state): void {
                                        $this->dispatch('toggleAgenda');
                                    }),
                                Toggle::make('registros')
                                    ->reactive()
                                    ->label('Registros Funcionais')
                                    ->inline(false)
                                    ->afterStateUpdated(function ($state): void {
                                        $this->dispatch('toggleAgenda');
                                    }),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsed(true)
                    ->columns(1),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            EventoCalendarWidget::class,
        ];
    }
}
