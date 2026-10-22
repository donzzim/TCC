<?php

namespace App\Filament\Resources\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Administrativo\DemandaResource\Pages;
use App\Filament\Resources\Administrativo\DemandaResource\RelationManagers;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UserResource;
use App\Models\Administrativo\Agenda;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Administrativo\Tarefas;
use App\Models\Escola\TipoLocal;
use App\Models\Lotacao;
use App\Models\Settings;
use App\Models\User;
use App\Tables\Columns\ProgressColumn;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ContentTabPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class DemandaResource extends Resource
{
    protected static ?string $model = Demanda::class;
    protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Demanda';
    protected static ?string $puralModelLabel = 'Demandas';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-folder-plus';
    protected static ?string $navigationLabel = 'Demandas';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 1;
    public static function getFormfields(): array
    {
        return [
            Section::make('Demanda')
                ->schema([
                    Forms\Components\Select::make('tipodemanda_id')
                        ->relationship(
                            name: 'tipodemanda',
                            titleAttribute: 'descricao',
                            modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'TIPO_DEMANDA'),
                        )
                        ->label('Tipo Demanda')
                        ->live()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\Hidden::make('grupo')
                                ->default('TIPO_DEMANDA'),
                            // Forms\Components\TextInput::make('codigo')
                            //         ->label('Código')
                            //         ->label('Código')
                            //         ->required()
                            //         ->maxLength(255),
                            Forms\Components\TextInput::make('descricao')
                                ->label('Descrição')
                                ->label('Descrição')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columnSpan(1),
                    Select::make('local_id')
                        ->label('Local')
                        ->live()
                        ->required()
                        ->afterStateHydrated(function (?Model $record, Set $set) {
                            if ($record) {
                                $agenda = $record->agendas()->first();
                                if ($agenda) {
                                    $set('local_id', $agenda->local_id);
                                }
                            }
                        })
                        ->options(TipoLocal::all()->pluck('descricao', 'id'))
                        ->hidden(function (Get $get) {
                            return $get('tipodemanda_id') != config('settings.tipodemanda.agendamento');
                        })
                        ->required()
                        ->searchable()
                        ->columnSpan(3)
                        ->lazy(),
                    DateTimePicker::make('data_prevista')
                        ->label('Data Inicio')
                        ->live(onBlur: true)

                        ->required()
                        ->columnSpan(3)
                        ->lazy()
                        ->hidden(function (Get $get) {
                            if ($get('local_id')) {
                                return false;
                            }
                            return true;
                        }),

                    DateTimePicker::make('data_termino')
                        ->label('Data Termino')
                        ->required()
                        ->live()
                        ->columnSpan(3)
                        ->lazy()
                        ->hidden(function (Get $get) {
                            if ($get('local_id')) {
                                return false;
                            }
                            return true;
                        }),

                    Forms\Components\TextInput::make('titulo')
                        ->label('Tí­tulo')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),

                    Forms\Components\Select::make('indicado_por')
                        ->relationship(name: 'indicado', titleAttribute: 'name')
                        ->searchable(['name'])
                        ->label('Solicitado por')
                        ->preload()
                        ->required()
                        ->default(Auth::user()->id)
                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(2),
                    Forms\Components\Select::make('tags')
                        ->label('Tags')
                        ->multiple()
                        ->relationship('tags', 'descricao')
                        ->searchable()
                        ->preload()
                        ->createOptionForm(
                            TagResource::getFormFields()
                        )
                        ->columnSpan(2),

                    Forms\Components\RichEditor::make('descricao')
                        ->label('Descrição')
                        ->required()
                        ->columnSpan(4),
                ])
                ->columnSpan(4)
                ->columns(4),

            Section::make('Curso/Evento')
                ->schema([
                    Forms\Components\TextInput::make('nome_instrutor')
                        ->label('Nome do Instrutor')
                        ->required(fn(Get $get) => $get('tipodemanda_id') == config('settings.tipodemanda.pedagogica'))
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('data_prevista')
                        ->label('Previsto para')
                        ->required(fn(Get $get) => $get('tipodemanda_id') == config('settings.tipodemanda.pedagogica'))
                        ->maxLength(255)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('qtde_horas')
                        ->label('Qtde Horas')
                        ->required(fn(Get $get) => $get('tipodemanda_id') == config('settings.tipodemanda.pedagogica'))
                        ->default(0)
                        ->maxLength(255)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('numero_participantes')
                        ->label('Núm. Participantes')
                        ->default(0)
                        ->required(fn(Get $get) => $get('tipodemanda_id') == config('settings.tipodemanda.pedagogica'))
                        ->maxLength(255)
                        ->columnSpan(1),
                ])
                ->columnSpan(2)
                ->columns(2),

            Forms\Components\Repeater::make('registros')
                ->defaultItems(0)
                ->label('Equipe de Trabalho')
                ->relationship()
                ->schema(
                    RegistroFuncionalResource::getFormfieldsEquipeTrabalho()
                )
                ->columnSpan(6)
                ->columns(6),

        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipodemanda.descricao')
                    ->label('Tipo Demanda')
                    ->sortable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Tí­tulo')
                    ->wrap()
                    ->sortable()
                    // ->description(fn (Demanda $record): HtmlString => new HtmlString("{$record->descricao}"))
                    ->description(fn(Demanda $record): HtmlString => new HtmlString("Instrutor: {$record->nome_instrutor} Previsto para: {$record->data_prevista}"))
                    ->searchable(),

                Tables\Columns\ImageColumn::make('indicado.avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/admin-tie.png'))
                    ->circular(),

                Tables\Columns\TextColumn::make('indicado.name')
                    ->label('Solicitado por')
                    ->formatStateUsing(function (string $state) {
                        $words = explode(' ', $state);
                        return reset($words) . ' ' . end($words);
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tags.descricao')
                    ->label('Tags')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->label('Criado em')
                    ->sortable(),
                Tables\Columns\TextColumn::make('card.descricao')
                    ->label('Status')
                    // ->description(fn (Demanda $record): HtmlString => new HtmlString("{$record->indicado->name}"))
                    ->searchable()
                    ->sortable()
                    ->color(function (Demanda $record) {
                        return Color::hex($record->card->cor);
                    })
                    ->badge(),
                Tables\Columns\ToggleColumn::make('ativo'),
                ProgressColumn::make('list_id')
                    ->label('Progresso'),
                // LinksColumn::make('id')
                //     ->label('Links'),
            ])
            ->filters([
                Tables\Filters\Filter::make('ativo')
                    ->toggle()
                    ->default()
                    ->query(fn(Builder $query): Builder => $query->where('ativo', 1)),
                Tables\Filters\SelectFilter::make('demanda')
                    ->options(Demanda::all()->pluck('titulo', 'id'))
                    ->searchable(['titulo'])
                    ->preload()
                    ->attribute('id')
                    ->default(request()->get('demanda_id'))
                    ->columnSpan(6),
                Tables\Filters\SelectFilter::make('tipodemanda')
                    ->label('Tipo Demanda')
                    ->relationship(
                        name: 'tipodemanda',
                        titleAttribute: 'descricao',
                        modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'TIPO_DEMANDA'),
                    )
                    ->searchable(['descricao'])
                    ->preload()
                    ->attribute('tipodemanda_id')
                    ->default(request()->get('tipodemanda_id'))
                    ->columnSpan(6),
                Tables\Filters\SelectFilter::make('situacao')
                    ->label('Situação')
                    ->relationship(
                        name: 'card',
                        titleAttribute: 'descricao',
                    )
                    ->searchable(['descricao'])
                    ->preload()
                    ->attribute('card_id')
                    ->default(request()->get('card_id'))
                    ->columnSpan(6),
                Tables\Filters\SelectFilter::make('tags')
                    ->label('Tags')
                    ->multiple()
                    ->relationship('tags', 'descricao')
                    ->preload()
                    ->columnSpan(6),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->headerActions([
                Action::make('levantamento')
                    ->label(function () {
                        $ativo = config('settings.demandas.levantamento');
                        if (!$ativo) {
                            return 'Ativar Levantamento';
                        }
                        return 'Desativar Levantamento';
                    })

                    ->color(function () {
                        $ativo = config('settings.demandas.levantamento');
                        if (!$ativo) {
                            return 'success';
                        }
                        return 'danger';
                    })
                    ->requiresConfirmation()
                    ->action(function () {
                        $ativo = config('settings.demandas.levantamento');
                        if (!$ativo) {
                            $value = ['levantamento' => true];
                            $settings = Settings::where('key', 'demandas')->first();
                            $settings->update(
                                ['value' => $value]
                            );
                            Notification::make()
                                ->title('Levantamento de Necessidades Ativado!')
                                ->success()
                                ->send();
                        } else {
                            $value = ['levantamento' => false];
                            $settings = Settings::where('key', 'demandas')->first();
                            $settings->update(
                                ['value' => $value]
                            );
                            Notification::make()
                                ->title('Levantamento de Necessidades Desativado!')
                                ->danger()
                                ->send();
                        }
                    })

                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                // ActionGroup::make([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),

                Action::make('acoes')
                    ->hiddenLabel()
                    ->tooltip('ações')
                    ->icon('heroicon-o-numbered-list')
                    ->color('success')
                    ->accessSelectedRecords()
                    ->form([
                        Forms\Components\CheckboxList::make('tarefas')
                            ->label('Tarefas pendentes')
                            ->required()
                            ->columns(2)
                            ->allowHtml()
                            ->options(function (Model $demanda) {
                                $proximo = $demanda->card->proximo;
                                $tarefas = DB::table('adm_tarefas as tarefas')
                                    ->select('tarefas.id', 'tarefas.descricao', 'cards.descricao AS card', 'lists.descricao AS list')
                                    ->join('adm_cards as cards', 'tarefas.card_id', '=', 'cards.id')
                                    ->join('adm_lists as lists', 'tarefas.list_id', '=', 'lists.id')
                                    ->where('tarefas.demanda_id', $demanda->id)
                                    ->whereNull('tarefas.data_concluida')
                                    // ->whereIn('card_id',[$proximo, $demanda->card_id])
                                    ->orderByRaw('lists.ordem, cards.ordem')
                                    ->get();
                                $options = [];
                                foreach ($tarefas as $tarefa) {
                                    $options[$tarefa->id] = '<span class="text-xs text-red-600">' . $tarefa->list . '</span><br/>' .
                                        '<span class="text-xs text-red-400">' . $tarefa->card . '</span><br/>' .
                                        '<span class="text-xs text-slate-500">' . $tarefa->descricao . '</span>';
                                }
                                return $options;
                            }),
                    ])
                    ->action(function (array $data, Model $demanda) {
                        // dd($demanda);
                        try {
                            DB::beginTransaction();
                            $updateTarefas = Tarefas::whereIn('id', $data['tarefas'])
                                ->update([
                                    'data_concluida' => now(),
                                    'atualizado_por' => Auth::user()->id,
                                ]);

                            $tarefasPendentes = DB::table('adm_tarefas as tarefas')
                                ->select('tarefas.id', 'tasks.id AS task', 'cards.id AS card', 'lists.id AS list')
                                ->join('adm_lists as lists', 'tarefas.list_id', '=', 'lists.id')
                                ->join('adm_cards as cards', 'tarefas.card_id', '=', 'cards.id')
                                ->join('adm_tasks as tasks', 'tarefas.task_id', '=', 'tasks.id')
                                ->whereNull('tarefas.data_concluida')
                                ->where('tarefas.demanda_id', '=', $demanda->id)
                                ->orderByRaw('tasks.ordem')
                                ->get();

                            foreach ($tarefasPendentes as $tarefa) {
                                $updateDemanda = Demanda::where('id', $demanda->id)
                                    ->update([
                                        'list_id' => $tarefa->list,
                                        'card_id' => $tarefa->card,
                                        'atualizado_por' => Auth::user()->id,
                                    ]);
                                break;
                            }


                            Notification::make()
                                ->title('Tarefa(s) atualizada(s) corretamente!')
                                ->success()
                                ->send();

                            DB::commit();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),
                // ])
                // ->iconButton()
                // ->icon('heroicon-o-bars-arrow-down'),
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),
                // ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CursosRelationManager::class,
            RelationManagers\TarefasRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDemandas::route('/'),
            'create' => Pages\CreateDemanda::route('/create'),
            'edit' => Pages\EditDemanda::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('card_id', '=', 1)->count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->titulo;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'titulo',
            'descricao',
            'nome_instrutor',
            'indicado.name',
            'tags.descricao'
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Tipo' => $record->tipodemanda->descricao,
            'Indicado por' => $record->indicado->name,
            'Tí­tulo' => $record->titulo,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['tipodemanda', 'indicado']);
    }
}
