<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MateriaisDoSetorModal extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public int $setorId;

    public function mount($setorId): void
    {
        $this->setorId = $setorId;
    }

    public function table(Table $table): Table
    {
        return BemMovelResource::table($table)
            ->queryStringIdentifier('materiaisSetor')
            ->query(
                BemMovelResource::getEloquentQuery()
                    ->where('Setor', $this->setorId)
            )
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table-modal');
    }
}
