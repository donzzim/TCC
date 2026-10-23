<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\CreateInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\EditInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\RelationManagers\UnidadesInventariadasRelationManager;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\InventarioAccess;
use App\Filament\Egap\Interno\Permissions\Patrimonio\InventarioGestaoResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Throwable;

class InventarioResource extends InventarioGestaoResource
{
    protected static ?string $model = Inventario::class;

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Gestão do Inventário';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $pluralModelLabel = 'Gestão do Inventário';

    protected static ?string $modelLabel = 'Inventário';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'bens-moveis/inventarios';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Inventário')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Dados gerais')
                            ->icon('heroicon-m-adjustments-horizontal')
                            ->schema([
                                Section::make('Identificação e período')
                                    ->description('Defina a numeração, vigência e situação do inventário.')
                                    ->icon('heroicon-o-calendar-days')
                                    ->schema([
                                        Grid::make(5)->schema([
                                            TextInput::make('num_inventario')
                                                ->label('Inventário Nº')
                                                ->required()
                                                ->maxLength(50),

                                            TextInput::make('ano_inventario')
                                                ->label('Ano')
                                                ->numeric()
                                                ->minValue(2000)
                                                ->maxValue((int) date('Y') + 1)
                                                ->default(date('Y'))
                                                ->required(),

                                            DatePicker::make('inicio_inventario')
                                                ->label('Início')
                                                ->displayFormat('d/m/Y')
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calcularDiasInventario($get, $set)),

                                            DatePicker::make('termino_inventario')
                                                ->label('Término')
                                                ->afterOrEqual('inicio_inventario')
                                                ->displayFormat('d/m/Y')
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calcularDiasInventario($get, $set)),

                                            TextInput::make('dias')
                                                ->label('Dias')
                                                ->numeric()
                                                ->suffix('dias')
                                                ->readOnly(),

                                            Select::make('situacao')
                                                ->label('Situação')
                                                ->options(Inventario::situacoes())
                                                ->native(false)
                                                ->default(Inventario::SITUACAO_A_INVENTARIAR)
                                                ->required()
                                                // a conferência (app e web) usa o inventário em execução
                                                ->rules([fn(?Inventario $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                                    $outro = Inventario::query()
                                                        ->emExecucao()
                                                        ->when($record, fn($query) => $query->whereKeyNot($record->getKey()))
                                                        ->first();

                                                    if ($value === Inventario::SITUACAO_EM_EXECUCAO && $outro) {
                                                        $fail("O inventário {$outro->num_inventario}/{$outro->ano_inventario} já está em execução. Conclua-o antes de iniciar outro.");
                                                    }
                                                }])
                                                ->columnSpanFull(),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Comissões')
                            ->icon('heroicon-m-users')
                            ->schema([
                                Section::make('Comissão do inventário')
                                    ->description('Informe os membros responsáveis pelo inventário.')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        Repeater::make('comissoes')
                                            ->relationship('comissoes')
                                            ->schema([
                                                Select::make('comissao')
                                                    ->label('Comissão')
                                                    ->options([
                                                        'Permanente' => 'Permanente',
                                                        'Especial' => 'Especial',
                                                    ])
                                                    ->native(false)
                                                    ->required(),

                                                Select::make('funcao')
                                                    ->label('Função')
                                                    ->options([
                                                        'Presidente' => 'Presidente',
                                                        'Membro' => 'Membro',
                                                    ])
                                                    ->native(false)
                                                    ->required(),

                                                Select::make('nome')
                                                    ->label('Membro')
                                                    ->relationship('membroRef', 'name')
                                                    ->searchable()
                                                    ->native(false)
                                                    // obrigatório ao adicionar; registros antigos apontam para pessoas que
                                                    // não existem mais no cadastro e não podem impedir salvar o inventário
                                                    ->required(fn(?Model $record): bool => $record === null),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'lg' => 3,
                                            ])
                                            ->defaultItems(0)
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->itemLabel(fn(array $state): string => collect([
                                                $state['comissao'] ?? null,
                                                $state['funcao'] ?? null,
                                            ])->filter()->join(' - ') ?: 'Membro da comissão')
                                            ->addActionLabel('Adicionar membro'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    /** Campos de uma unidade inventariada (modal da tabela de unidades do inventário). */
    public static function camposUnidadeInventariada(): array
    {
        return [
            Grid::make([
                'default' => 1,
                'md' => 2,
                'xl' => 12,
            ])
                ->schema([
                    Radio::make('tipo_abrangencia')
                        ->label('Abrangência')
                        ->options([
                            'unidade' => 'Unidade inteira',
                            'setor' => 'Setor específico',
                        ])
                        ->descriptions([
                            'unidade' => 'Inventaria toda a unidade organizacional selecionada.',
                            'setor' => 'Inventaria somente o setor selecionado.',
                        ])
                        ->default('unidade')
                        ->inline()
                        ->inlineLabel(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Radio $component, ?InventarioUnidade $record): void {
                            $component->state($record?->tipoAbrangencia() ?? 'unidade');
                        })
                        ->afterStateUpdated(function (Set $set): void {
                            $set('unidades', null);
                        })
                        ->required()
                        ->columnSpan([
                            'default' => 'full',
                            'md' => 1,
                            'xl' => 4,
                        ]),

                    Select::make('unidades')
                        ->label(fn(Get $get): string => $get('tipo_abrangencia') === 'setor'
                            ? 'Setor'
                            : 'Unidade Organizacional')
                        ->placeholder(fn(Get $get): string => $get('tipo_abrangencia') === 'setor'
                            ? 'Selecione o setor'
                            : 'Selecione a unidade organizacional')
                        // busca no servidor em vez de pré-carregar os ~1.500 setores
                        ->searchable()
                        ->getSearchResultsUsing(fn(string $search, Get $get): array => self::buscarAbrangencia($get, $search))
                        ->getOptionLabelUsing(fn($value): ?string => self::rotuloSetor($value))
                        ->searchPrompt('Digite parte do nome')
                        ->native(false)
                        ->required()
                        ->columnSpan([
                            'default' => 'full',
                            'md' => 1,
                            'xl' => 8,
                        ]),

                    DatePicker::make('data_inicio')
                        ->label('Início')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calcularDiasUnidade($get, $set))
                        ->columnSpan([
                            'default' => 'full',
                            'md' => 1,
                            'xl' => 3,
                        ]),

                    DatePicker::make('data_termino')
                        ->label('Término')
                        ->afterOrEqual('data_inicio')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calcularDiasUnidade($get, $set))
                        ->columnSpan([
                            'default' => 'full',
                            'md' => 1,
                            'xl' => 3,
                        ]),

                    TextInput::make('dias')
                        ->label('Dias')
                        ->numeric()
                        ->suffix('dias')
                        ->readOnly()
                        ->columnSpan([
                            'default' => 'full',
                            'md' => 1,
                            'xl' => 2,
                        ]),

                    Select::make('situacao')
                        ->label('Situação')
                        ->options(Inventario::situacoes())
                        ->native(false)
                        ->default(Inventario::SITUACAO_A_INVENTARIAR)
                        ->required()
                        ->columnSpan([
                            'default' => 'full',
                            'md' => 1,
                            'xl' => 4,
                        ]),

                    Repeater::make('equipes')
                        ->label('Equipes de Campo')
                        ->relationship('equipes')
                        ->schema([
                            Select::make('funcao')
                                ->label('Função')
                                ->options([
                                    'Líder' => 'Líder',
                                    'Membro' => 'Membro',
                                ])
                                ->native(false)
                                ->required(),

                            Select::make('integrante')
                                ->label('Integrante')
                                ->relationship('integrantesRef', 'name')
                                ->searchable()
                                ->native(false)
                                // obrigatório ao adicionar; registros antigos apontam para pessoas que
                                // não existem mais no cadastro e não podem impedir salvar o inventário
                                ->required(fn(?Model $record): bool => $record === null),
                        ])
                        ->columns([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn(array $state): string => $state['funcao'] ?? 'Integrante')
                        ->addActionLabel('Adicionar integrante')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('num_inventario', 'Inventário Nº', isFirstColumn: true)
                    ->badge()
                    ->weight('medium'),
                TableColumns::text('ano_inventario', 'Ano'),
                TableColumns::date('inicio_inventario', 'Início'),
                TableColumns::date('termino_inventario', 'Término'),
                TableColumns::text('dias', 'Dias')->suffix(' dias'),
                TableColumns::text('situacao', 'Situação')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Inventario::rotuloSituacao($state))
                    ->color(fn(?string $state): string => Inventario::corSituacao($state)),

                TableColumns::text('unidades_inventariadas_count', 'Unidades')
                    ->counts('unidadesInventariadas')
                    ->searchable(false)
                    ->sortable(false)
                    ->icon('heroicon-m-building-office')
                    ->iconColor('primary')
                    ->badge()
                    ->color('gray')
                    ->tooltip('Visualizar unidades')
                    ->action(self::unidadesInventariadasTableAction()),
                TableColumns::text('comissoes_count', 'Comissões')
                    ->counts('comissoes')
                    ->searchable(false)
                    ->sortable(false)
                    ->icon('heroicon-m-users')
                    ->iconColor('success')
                    ->badge()
                    ->color('gray')
                    ->tooltip('Visualizar comissões')
                    ->action(self::comissoesTableAction()),
            ])
            ->filters([
                SelectFilter::make('situacao')
                    ->columnSpan(2)
                    ->label('Situação')
                    ->options(Inventario::situacoes()),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                // as ações padrão não consultam canEdit/canDelete do recurso
                ...array_map(fn(Action $action): Action => match (true) {
                    $action instanceof EditAction => $action->visible(fn(Inventario $record): bool => self::canEdit($record)),
                    $action instanceof DeleteAction => $action->visible(fn(Inventario $record): bool => self::canDelete($record)),
                    default => $action,
                }, TableDefaults::actions()),
                self::atualizarSituacaoTableAction(),
            ]);
    }

    private static function atualizarSituacaoTableAction(): Action
    {
        return Action::make('atualizar_situacao')
            ->icon('heroicon-o-clipboard-document-list')
            ->color('gray')
            ->requiresConfirmation()
            ->hiddenLabel()
            ->modalHeading('Atualizar situação do inventário')
            ->modalDescription('Os bens ativos dos setores/unidades deste inventário serão marcados como A INVENTARIAR. Bens já conferidos neste inventário não são alterados.')
            ->visible(fn(): bool => InventarioAccess::podeAbrir())
            ->action(function (Inventario $record): void {
                try {
                    $totalAtualizado = self::atualizarSituacaoBensInventario($record);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível atualizar a situação.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $notification = Notification::make()
                    ->title('Situação atualizada.')
                    ->body("{$totalAtualizado} bem(ns) marcado(s) como A INVENTARIAR.");

                $totalAtualizado > 0
                    ? $notification->success()
                    : $notification->warning();

                $notification->send();
            })
            ->tooltip('Atualizar situação');
    }

    private static function buscarAbrangencia(Get $get, string $search): array
    {
        $termo = '%'.trim($search).'%';

        return Setores::query()
            ->when(
                $get('tipo_abrangencia') === 'setor',
                fn($query) => $query->setoresInventariaveis(),
                fn($query) => $query->unidadesInventariaveis(),
            )
            ->where(fn($query) => $query->where('setor', 'like', $termo)->orWhere('unidadeorganizacional', 'like', $termo))
            ->with('pai:id,unidadeorganizacional,setor')
            ->orderBy('setor')
            ->limit(50)
            ->get(['id', 'unidadeorganizacional', 'setor', 'codigopai'])
            ->mapWithKeys(fn(Setores $setor): array => [
                $setor->id => $setor->rotuloInventario(),
            ])
            ->toArray();
    }

    private static function rotuloSetor(mixed $setorId): ?string
    {
        return blank($setorId)
            ? null
            : Setores::query()->with('pai:id,unidadeorganizacional,setor')->find($setorId)?->rotuloInventario();
    }

    private static function rotuloItemUnidadeInventariada(array $state): string
    {
        return self::rotuloSetor($state['unidades'] ?? null) ?? 'Unidade inventariada';
    }

    private static function unidadesInventariadasTableAction(): Action
    {
        return TableModalAction::make('visualizar_unidades_inventario')
            ->modalHeading(fn(Inventario $record): string => "Unidades do Inventário {$record->num_inventario}/{$record->ano_inventario}")
            ->modalContent(fn(Inventario $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.inventario-unidades-modal',
                    ['inventarioId' => (int) $record->getKey()],
                    "inventario-unidades-{$record->getKey()}",
                )
            ));
    }

    private static function comissoesTableAction(): Action
    {
        return TableModalAction::make('visualizar_comissoes_inventario')
            ->modalHeading(fn(Inventario $record): string => "Comissões do Inventário {$record->num_inventario}/{$record->ano_inventario}")
            ->modalContent(fn(Inventario $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.inventario-comissoes-modal',
                    ['inventarioId' => (int) $record->getKey()],
                    "inventario-comissoes-{$record->getKey()}",
                )
            ));
    }

    private static function atualizarSituacaoBensInventario(Inventario $inventario): int
    {
        [$unidades, $setores] = self::idsAbrangenciaInventario($inventario);

        if ($unidades->isEmpty() && $setores->isEmpty()) {
            return 0;
        }

        return BemMovel::query()
            ->where(function ($query) use ($unidades, $setores): void {
                if ($setores->isNotEmpty()) {
                    $query->whereIn('Setor', $setores);
                }

                if ($unidades->isNotEmpty()) {
                    $method = $setores->isNotEmpty() ? 'orWhereIn' : 'whereIn';

                    $query->{$method}('UnidadeJudiciaria', $unidades);
                }
            })
            // só bens que entram na conferência (ativo, em trânsito, a inventariar)
            ->whereIn('SituacaoBem', [1, 7, 8])
            // rodar de novo não pode apagar o que já foi conferido neste inventário
            ->whereNot(fn($query) => $query
                ->where('id_inventario', $inventario->getKey())
                ->whereIn('sit_inventario', ['LOCALIZADO', 'NÃO LOCALIZADO', 'EM TRANSFERENCIA']))
            ->update([
                'sit_inventario' => 'A INVENTARIAR',
                'id_inventario' => $inventario->getKey(),
            ]);
    }

    private static function idsAbrangenciaInventario(Inventario $inventario): array
    {
        $selecionados = $inventario->unidadesInventariadas()
            ->with('unidade')
            ->get()
            ->pluck('unidade')
            ->filter();

        $unidades = $selecionados
            ->filter(fn(Setores $setor): bool => $setor->inventariaUnidadeInteira())
            ->pluck('id')
            ->unique()
            ->values();

        $setoresDiretos = $selecionados
            ->reject(fn(Setores $setor): bool => $setor->inventariaUnidadeInteira())
            ->pluck('id');

        $setoresDasUnidades = Setores::query()
            ->whereIn('codigopai', $unidades)
            ->setoresInventariaveis()
            ->pluck('id');

        $setores = $setoresDiretos
            ->merge($setoresDasUnidades)
            ->merge($unidades)
            ->unique()
            ->values();

        return [$unidades, $setores];
    }

    private static function calcularDiasInventario(Get $get, Set $set): void
    {
        self::calcularDias($get('inicio_inventario'), $get('termino_inventario'), $set);
    }

    private static function calcularDiasUnidade(Get $get, Set $set): void
    {
        self::calcularDias($get('data_inicio'), $get('data_termino'), $set);
    }

    private static function calcularDias(mixed $inicio, mixed $termino, Set $set): void
    {
        if (! $inicio || ! $termino) {
            $set('dias', null);

            return;
        }

        // o término é validado como >= início; aqui só não deixa o número ficar negativo
        $set('dias', max(0, (int) Carbon::parse($inicio)->diffInDays(Carbon::parse($termino), false)));
    }

    public static function canDelete(Model $record): bool
    {
        /** @var Inventario $record */
        return parent::canDelete($record) && ! $record->temConferencia();
    }

    public static function getRelations(): array
    {
        return [
            UnidadesInventariadasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventarios::route('/'),
            'create' => CreateInventario::route('/create'),
            'edit' => EditInventario::route('/{record}/edit'),
        ];
    }
}
