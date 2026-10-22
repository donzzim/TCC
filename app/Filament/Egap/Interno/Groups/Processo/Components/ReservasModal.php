<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Components;

use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\ReservaResource;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Schemas\ReservaForm;
use App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Tables\ReservaTable;
use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\AnexosProcessoResource;
use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Schemas\AnexosProcessoForm;
use App\Filament\Egap\Interno\Groups\Processo\Resources\MateriaisResource;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReservasModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $processoId;

    public function mount(int $processoId): void
    {
        $this->processoId = $processoId;
    }

    public function table(Table $table): Table
    {
        return ReservaTable::configure($table)
            ->query(
                ReservaResource::getEloquentQuery()
                    ->where('processo_no', $this->processoId)
            )
            ->recordActions([
                EditAction::make()
                    ->tooltip('Editar')
                    ->modalHeading(fn() => "Editar reservas do Processo #{$this->processoId}")
                    ->schema(fn(Schema $schema): Schema => ReservaForm::configure($schema))
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->outlined()
                    ->label('Adicionar Reserva')
                    ->modalHeading(fn() => "Adicionar reserva ao Processo #{$this->processoId}")
                    ->schema(fn(Schema $schema): Schema => ReservaForm::configure($schema))
                    ->icon(Heroicon::OutlinedFolderPlus)
                    ->fillForm(fn(): array => ['processo_no' => $this->processoId]),
            ]);
    }
}
