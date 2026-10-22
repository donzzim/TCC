<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\ServicosGerais\Contrato;
use App\Models\egap\ServicosGerais\ContratoLocalidade;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Gerencia as localidades (setores) participantes de um contrato — só as
 * marcadas como ativas entram na próxima geração de formulários (legado:
 * bloco "Novas Localidades" de avacadastro.php, que gravava em
 * ava_localidades).
 *
 * Aberto num modal a partir de {@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Pages\Gestor}.
 */
class LocalidadesTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public int $contratoId;

    public function mount(int $contratoId): void
    {
        $this->contratoId = $contratoId;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->heading('Localidades do Contrato')
            ->headerActions([
                $this->adicionarLocalidadeAction(),
            ])
            ->columns([
                TableColumns::text('setor.setor', 'Localidade', isFirstColumn: true)
                    ->wrap(),
            ])
            ->recordActions([
                $this->alternarAtivoAction(),
            ])
            ->paginated(false)
            ->defaultSort('setor.setor')
            ->emptyStateHeading('Nenhuma localidade cadastrada para este contrato');
    }

    private function adicionarLocalidadeAction(): Action
    {
        return Action::make('adicionar_localidade')
            ->label('Adicionar Localidade')
            ->icon('heroicon-o-plus-circle')
            ->schema([
                Select::make('setor_id')
                    ->label('Localidade')
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->options(fn(): array => Setores::query()
                        ->whereNotIn('id', ContratoLocalidade::query()
                            ->where('contrato_id', $this->contratoId)
                            ->pluck('setor_id'))
                        ->orderBy('setor')
                        ->pluck('setor', 'id')
                        ->toArray()),
            ])
            ->action(function (array $data): void {
                ContratoLocalidade::query()->create([
                    'contrato_id' => $this->contratoId,
                    'setor_id' => $data['setor_id'],
                    'ativo' => true,
                ]);

                Notification::make()
                    ->title('Localidade adicionada.')
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    private function alternarAtivoAction(): Action
    {
        return Action::make('alternar_ativo')
            ->label(fn(ContratoLocalidade $record): string => $record->ativo ? 'Desativar' : 'Ativar')
            ->icon(fn(ContratoLocalidade $record): string => $record->ativo ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
            ->color(fn(ContratoLocalidade $record): string => $record->ativo ? 'danger' : 'success')
            ->action(function (ContratoLocalidade $record): void {
                $record->update(['ativo' => ! $record->ativo]);
            });
    }

    private function getQuery(): Builder
    {
        return ContratoLocalidade::query()
            ->where('contrato_id', $this->contratoId)
            ->with('setor');
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
