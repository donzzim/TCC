<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Components;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\EmpenhoResource;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\Tables\EmpenhoTable;
use App\Filament\Egap\Support\TableModalComponent;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmpenhosModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $processoId;

    public function mount(int $processoId): void
    {
        $this->processoId = $processoId;
    }

    public function table(Table $table): Table
    {
        return EmpenhoTable::configure($table)
            ->query(
                EmpenhoResource::getEloquentQuery()
                    ->where('processo_no', $this->processoId)
            )
            ->recordActions([
                EditAction::make()
                    ->tooltip('Editar')
                    ->modalHeading(fn() => "Editar empenhos do Processo #{$this->processoId}")
                    ->schema(fn(Schema $schema): Schema => EmpenhoResource::form($schema))
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->outlined()
                    ->label('Adicionar Empenho')
                    ->modalHeading(fn() => "Adicionar empenho ao Processo #{$this->processoId}")
                    ->schema(fn(Schema $schema): Schema => EmpenhoResource::form($schema))
                    ->icon(Heroicon::OutlinedMagnifyingGlassPlus)
                    ->fillForm(fn(): array => ['processo_no' => $this->processoId]),
            ]);
    }
}
