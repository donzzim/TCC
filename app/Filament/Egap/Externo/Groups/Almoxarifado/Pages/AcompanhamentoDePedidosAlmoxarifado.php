<?php

namespace App\Filament\Egap\Externo\Groups\Almoxarifado\Pages;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidosAlmoxarifadoTable;
use App\Filament\Egap\Externo\Utils\AcompanhamentoDePedidosPage;

class AcompanhamentoDePedidosAlmoxarifado extends AcompanhamentoDePedidosPage
{
    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'almoxarifado/acompanhamento-de-pedidos';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string | \UnitEnum | null $navigationGroup = 'Almoxarifado';

    protected static ?string $navigationLabel = 'Acompanhamento de Pedidos';

    protected static ?string $title = 'Acompanhamento de Pedidos';

    public function component(): string
    {
        return PedidosAlmoxarifadoTable::class;
    }
}
