<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Components;

use App\Filament\Egap\Interno\Groups\Pedidos\Resources\ValidarPedidoResource;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ValidarPedidoItensModal extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public int $pedidoId;

    public function mount($pedidoId): void
    {
        $this->pedidoId = (int) $pedidoId;
    }

    public function table(Table $table): Table
    {
        return ValidarPedidoResource::table($table)
            ->queryStringIdentifier('itensPedido')
            ->query(
                ValidarPedidoResource::getEloquentQuery()
                    ->where('idPedido', $this->pedidoId)
            )
            ->filters([])
            ->toolbarActions([]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table-modal');
    }
}
