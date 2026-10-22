<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages;

use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Groups\Pedidos\Components\EntregaRecolhimentoTable;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoPage;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class AgendamentoEntregaRecolhimento extends PedidoPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected string $view = 'egap.livewire.support.table-page';

    protected static ?string $navigationLabel = 'Agendamento da Entrega/Recolhimento';

    protected static ?string $title = 'Agendamento da Entrega/Recolhimento';

    protected static ?string $slug = 'agendamento-entrega-recolhimento';

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function component(): string
    {
        return EntregaRecolhimentoTable::class;
    }
}
