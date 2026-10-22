<?php

namespace App\Filament\Egap\Externo\Groups\PlanoContratacao\Components;

use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\PlanoContratacao\LimitePlano;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * CRUD dos limites anuais de PCA por setor (legado: `pla_limite.api.php`).
 */
class LimitesTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(LimitePlano::query()->with('setor'))
            ->columns([
                TableColumns::text('id_setor', 'Registro', isFirstColumn: true, isIdColumn: true),

                TableColumns::text('setor.setor', 'Setor')->wrap(),

                TextColumn::make('ano_limite')
                    ->label('Ano')
                    ->formatStateUsing(fn ($state): string => (string) optional($state)->year)
                    ->alignCenter(),

                TableColumns::money('limite', 'Limite'),

                TextColumn::make('consumido')
                    ->label('Consumido')
                    ->alignCenter()
                    ->state(fn (LimitePlano $record): float => LimitePlano::consumidoPara((int) $record->id_setor, (int) optional($record->ano_limite)->year))
                    ->money('BRL'),

                TextColumn::make('disponivel')
                    ->label('Disponível')
                    ->alignCenter()
                    ->state(fn (LimitePlano $record): float => (float) $record->limite - LimitePlano::consumidoPara((int) $record->id_setor, (int) optional($record->ano_limite)->year))
                    ->money('BRL'),
            ])
            ->headerActions([
                Action::make('adicionarLimite')
                    ->label('Adicionar limite')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('id_setor')
                            ->label('Setor organizacional')
                            ->options(fn (): array => Setores::query()
                                ->whereIn('cd_orgao', LimitePlano::SETORES_PAI)
                                ->orderBy('setor')
                                ->pluck('setor', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->native(false),

                        MoneyInput::make('limite')
                            ->label('Valor do limite')
                            ->required(),

                        TextInput::make('ano')
                            ->label('Ano de implementação')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default((int) date('Y') + 1)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        LimitePlano::create([
                            'id_setor' => $data['id_setor'],
                            'limite' => $data['limite'],
                            'ano_limite' => $data['ano'].'-01-01',
                        ]);

                        Notification::make()->title('Limite adicionado.')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('remover')
                    ->label('Remover')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (LimitePlano $record): void {
                        LimitePlano::query()
                            ->where('id_setor', $record->id_setor)
                            ->whereRaw('YEAR(ano_limite) = ?', [optional($record->ano_limite)->year])
                            ->delete();

                        Notification::make()->title('Limite removido.')->success()->send();
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('ano_limite', 'desc');
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
