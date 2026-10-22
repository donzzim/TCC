<?php

namespace App\Filament\Pages\Contratacao;

use UnitEnum;
use BackedEnum;
use App\Filament\Widgets\Contratacao\DashboardOrcamentoTable;
use App\Filament\Widgets\Contratacao\DashboardStatsOrcamento;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class DashboardOrcamento extends \Filament\Pages\Dashboard
{

    use HasFiltersForm;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $routePath = 'dashboard-orcamento';
    protected static ?string $title = 'Dashboard - Planejamento Orçamentário';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Orçamentário';
    protected static ?int $navigationSort = 1;

    // Definição do Formulário de Filtros
    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('ano')
                            ->label('Filtrar por Ano')
                            ->options(function () {
                                // Gera uma lista de anos (ex: 5 anos atrás até 1 ano no futuro)
                                $anos = range(now()->year - 5, now()->year + 1);
                                return array_combine($anos, $anos);
                            })
                            ->default(now()->year) // Default ano corrente
                            ->selectablePlaceholder(false) // Impede ficar vazio
                            ->live() // Atualiza os widgets assim que mudar
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(6) // Ajuste a largura conforme necessário
            ]);
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
        $arr = [900, 800, 700, 600, 500, 400, 300, 200, 100, 50];
        foreach ($arr as $value) {
            $cor = "rgb(" . $color[$value] . ")";
            $return[] = $cor;
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
            DashboardStatsOrcamento::class,
            DashboardOrcamentoTable::class,
        ];
    }
}
