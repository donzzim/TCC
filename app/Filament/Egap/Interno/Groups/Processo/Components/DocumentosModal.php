<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Components;

use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\AnexosProcessoResource;
use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Schemas\AnexosProcessoForm;
use App\Filament\Egap\Interno\Groups\Processo\Resources\AnexosProcessos\Tables\AnexosProcessosTable;
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

class DocumentosModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $processoId;

    public function mount(int $processoId): void
    {
        $this->processoId = $processoId;
    }

    public function table(Table $table): Table
    {
        return AnexosProcessosTable::configure($table)
            ->query(
                AnexosProcessoResource::getEloquentQuery()
                    ->where('num_processo', $this->processoId)
            )
            ->recordActions([
                EditAction::make()
                    ->tooltip('Editar')
                    ->modalHeading(fn() => "Editar anexo do Processo #{$this->processoId}")
                    ->schema(fn(Schema $schema): Schema => AnexosProcessoForm::configure($schema))
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->hiddenLabel(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->outlined()
                    ->label('Adicionar Anexo')
                    ->modalHeading(fn() => "Adicionar anexo ao Processo #{$this->processoId}")
                    ->schema(fn(Schema $schema): Schema => AnexosProcessoForm::configure($schema))
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->fillForm(fn(): array => ['num_processo' => $this->processoId]),
            ]);
    }
}
