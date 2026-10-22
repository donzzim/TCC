<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;

class HistoricoMovimentacoesModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $numPatrimonio;

    public function mount(int $numPatrimonio): void
    {
        $this->numPatrimonio = $numPatrimonio;
    }

    public function table(Table $table): Table
    {
        return TransferenciaBemResource::table($table)
            ->query(
                TransferenciaBemResource::getEloquentQuery()
                    ->where('NumPatrimonio', $this->numPatrimonio)
            );
    }
}
