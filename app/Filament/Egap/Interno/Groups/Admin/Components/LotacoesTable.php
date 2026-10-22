<?php

namespace App\Filament\Egap\Interno\Groups\Admin\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\User;
use App\Models\egap\UserLotacao;
use App\Models\egap\Cadastro\Setores;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class LotacoesTable extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $userId;

    public function mount(int $userId): void
    {
        $this->userId = $userId;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(
                UserLotacao::query()->where('user_id', $this->userId)
            )
            ->columns([
                TableColumns::text('setor.unidadeorganizacional', 'Unidade Organizacional', true),
                TableColumns::text('setor.setor', 'Setor', true),
                ToggleColumn::make('principal')
                    ->label('Principal')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function (UserLotacao $record, bool $state): void {
                        if (! $state) {
                            return;
                        }

                        UserLotacao::query()
                            ->where('user_id', $record->user_id)
                            ->whereKeyNot($record->getKey())
                            ->update(['principal' => false]);
                    })
                    ->alignCenter(),
            ])
            ->toolbarActions([])
            ->recordActions([
                EditAction::make()
                    ->tooltip('Editar')
                    ->schema(fn(Schema $schema): Schema => static::formSchema($schema))
                    ->mutateRecordDataUsing(function (array $data, UserLotacao $record): array {
                        $data['unidade_judiciaria'] = $record->setor?->codigopai;

                        return $data;
                    })
                    ->modalHeading(fn() => "Editar lotação ao Usuário EGAP #{$this->userId}")
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->tooltip('Excluir')
                    ->modalHeading('Excluir essa Lotação?')
                    ->hiddenLabel(),
            ])
            ->headerActions([
                Action::make('adicionar_lotacao')
                    ->outlined()
                    ->label('Adicionar Lotação')
                    ->modalHeading(fn() => "Adicionar lotação ao Usuário EGAP #{$this->userId}")
                    ->modalSubmitActionLabel('Adicionar')
                    ->schema(fn(Schema $schema): Schema => static::formSchema($schema))
                    ->icon(Heroicon::OutlinedSquaresPlus)
                    ->fillForm(fn(): array => ['user_id' => $this->userId])
                    ->action(function ($data) {
                        if (! $data){
                            Notification::make()
                                ->title('Ops! Algo deu errado')
                                ->body('Não foi possível registrar a lotação')
                                ->danger()
                                ->send();

                            return;
                        }

                        UserLotacao::query()->insert([
                            'user_id' => $this->userId,
                            'setor_id' => $data['setor_id'],
                            'atualizado_por' => auth(User::GUARD)->id(),
                            'principal' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Lotação registrada')
                            ->success()
                            ->send();
                    })

            ]);
    }

    private static function formSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id'),
                Select::make('unidade_judiciaria')
                    ->label('Unidade Judiciária')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->options(
                        fn () => Setores::query()
                            ->selectRaw('MIN(id) as id, unidadeorganizacional')
                            ->groupBy('unidadeorganizacional')
                            ->orderBy('unidadeorganizacional')
                            ->pluck('unidadeorganizacional', 'id')
                            ->toArray()
                    )
                    ->afterStateUpdated(fn(Set $set) => $set('setor_id', null)),

                Select::make('setor_id')
                    ->label('Setor')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(
                        fn(Get $get) => Setores::query()
                            ->when(
                                $get('unidade_judiciaria'),
                                fn($query, $codigoPai) => $query->where('codigopai', $codigoPai)
                            )
                            ->orderBy('setor')
                            ->pluck('setor', 'id')
                            ->toArray()
                    )
                    ->disabled(fn(Get $get) => blank($get('unidade_judiciaria'))),
            ]);
    }
}
