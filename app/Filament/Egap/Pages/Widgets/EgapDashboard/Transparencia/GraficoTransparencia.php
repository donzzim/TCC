<?php

namespace App\Filament\Egap\Pages\Widgets\EgapDashboard\Transparencia;

use App\Filament\Egap\Externo\Pages\Widgets\BaseChart;

abstract class GraficoTransparencia extends BaseChart
{
    protected ?int $segundosEmCache = 600;
}
