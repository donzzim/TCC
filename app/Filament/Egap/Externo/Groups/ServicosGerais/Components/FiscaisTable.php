<?php

namespace App\Filament\Egap\Externo\Groups\ServicosGerais\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\ServicosGerais\ContratoFiscal;
use App\Models\egap\ServicosGerais\ContratoLocalidade;
use App\Models\egap\UserEgap;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
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
 * Atribui usuários fiscais a uma localidade do contrato (legado: bloco
 * "Novos Fiscais" de avacadastro.php, que gravava em ava_funcao com
 * id_tipo='F' e concedia o grupo Joomla "Serviços Gerais - Avaliação" — a
 * concessão de grupo Joomla não faz sentido nesta versão, a permissão de
 * acesso ao painel do fiscal passa a ser resolvida por
 * {@see ContratoFiscal} diretamente).
 *
 * Aberto num modal a partir de {@see \App\Filament\Egap\Externo\Groups\ServicosGerais\Pages\Gestor}.
 */
class FiscaisTable extends Component implements HasActions, HasForms, HasTable
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
            ->heading('Fiscais do Contrato')
            ->headerActions([
                $this->atribuirFiscalAction(),
            ])
            ->columns([
                TableColumns::text('setor.setor', 'Localidade', isFirstColumn: true)
                    ->wrap(),
                TableColumns::text('user.name', 'Fiscal')
                    ->wrap(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->tooltip('Remover')
                    ->hiddenLabel(),
            ])
            ->paginated(false)
            ->defaultSort('setor.setor')
            ->emptyStateHeading('Nenhum fiscal atribuído a este contrato');
    }

    private function atribuirFiscalAction(): Action
    {
        return Action::make('atribuir_fiscal')
            ->label('Atribuir Fiscal')
            ->icon('heroicon-o-plus-circle')
            ->schema([
                Select::make('setor_id')
                    ->label('Localidade')
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->options(fn(): array => ContratoLocalidade::query()
                        ->where('contrato_id', $this->contratoId)
                        ->where('ativo', true)
                        ->with('setor')
                        ->get()
                        ->pluck('setor.setor', 'setor_id')
                        ->toArray()),
                Select::make('user_id')
                    ->label('Fiscal')
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->options(fn(): array => UserEgap::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->action(function (array $data): void {
                $jaExiste = ContratoFiscal::query()
                    ->where('contrato_id', $this->contratoId)
                    ->where('setor_id', $data['setor_id'])
                    ->where('user_id', $data['user_id'])
                    ->exists();

                if ($jaExiste) {
                    Notification::make()
                        ->title('Este fiscal já está atribuído a esta localidade.')
                        ->warning()
                        ->send();

                    return;
                }

                ContratoFiscal::query()->create([
                    'contrato_id' => $this->contratoId,
                    'setor_id' => $data['setor_id'],
                    'user_id' => $data['user_id'],
                ]);

                Notification::make()
                    ->title('Fiscal atribuído.')
                    ->success()
                    ->send();

                $this->resetTable();
            });
    }

    private function getQuery(): Builder
    {
        return ContratoFiscal::query()
            ->where('contrato_id', $this->contratoId)
            ->with(['setor', 'user']);
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
