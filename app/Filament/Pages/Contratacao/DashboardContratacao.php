<?php

namespace App\Filament\Pages\Contratacao;

use UnitEnum;
use BackedEnum;
use App\Filament\Widgets\Contratacao\DashboardContratacaoTable;
use App\Filament\Widgets\Contratacao\DashboardProcessoEventosPorTipoChart;
use App\Filament\Widgets\Contratacao\DashboardProcessosPorTipoChart;
use App\Filament\Widgets\Contratacao\DashboardStatsContratacao;

class DashboardContratacao extends \Filament\Pages\Dashboard
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $routePath = 'dashboard-contratacao';
    protected static ?string $title = 'Dashboard - Contratação';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string | UnitEnum | null $navigationGroup = 'Contratação';
    protected static ?int $navigationSort = 1;

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
            DashboardStatsContratacao::class,
            DashboardProcessosPorTipoChart::class,
            DashboardProcessoEventosPorTipoChart::class,
            DashboardContratacaoTable::class,
        ];
    }
}
