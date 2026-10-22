<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Pages;

use App\Filament\Egap\Externo\Groups\Patrimonio\Components\PedidosPatrimonioTable;
use App\Filament\Egap\Externo\Utils\AcompanhamentoDePedidosPage;

class AcompanhamentoDePedidosPatrimonio extends AcompanhamentoDePedidosPage
{
    protected static ?string $slug = '/patrimonio/acompanhamento-de-pedidos';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string | \UnitEnum | null $navigationGroup = 'Patrimônio';

    protected static ?string $navigationLabel = 'Acompanhamento de Pedidos';

    protected static ?string $title = 'Acompanhamento de Pedidos do Patrimônio';

    protected static ?int $navigationSort = 4;

    public function component(): string
    {
        return PedidosPatrimonioTable::class;
    }
}
