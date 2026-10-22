<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource\Pages\CreateInventarioUnidade;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource\Pages\EditInventarioUnidade;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource\Pages\ListInventarioUnidades;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class InventarioUnidadeResource extends PatrimonioResource
{
    protected static ?string $model = InventarioUnidade::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?string $slug = 'bens-moveis/unidades-inventariadas';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Unidades Inventariadas';

    protected static ?string $pluralModelLabel = 'Unidades Inventariadas';

    protected static ?string $modelLabel = 'Unidade Inventariada';

    protected static ?int $navigationSort = 13;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Unidades Inventariadas')
                            ->icon('heroicon-m-building-office')
                            ->schema([
                                Section::make('Planejamento da Unidade')
                                    ->description('Vincule a unidade ao inventário e informe o período de execução.')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Grid::make(6)->schema([
                                            Select::make('id_inventario')
                                                ->label('Inventário No')
                                                ->relationship('inventario', 'num_inventario')
                                                ->placeholder('Selecione o inventário')
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->required()
                                                ->columnSpanFull(),

                                            Select::make('tipo_abrangencia')
                                                ->label('Abrangência')
                                                ->options([
                                                    'unidade' => 'Unidade inteira',
                                                    'setor' => 'Setor',
                                                ])
                                                ->default('unidade')
                                                ->native(false)
                                                ->live()
                                                ->dehydrated(false)
                                                ->afterStateHydrated(function (Select $component, ?InventarioUnidade $record): void {
                                                    $component->state($record?->tipoAbrangencia() ?? 'unidade');
                                                })
                                                ->afterStateUpdated(function (Set $set): void {
                                                    $set('unidade_pai', null);
                                                    $set('unidades', null);
                                                })
                                                ->required()
                                                ->columnSpan(2),

                                            Select::make('unidade_pai')
                                                ->label('Unidade Organizacional')
                                                ->options(fn(): array => self::opcoesUnidadesOrganizacionais())
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->live()
                                                ->dehydrated(false)
                                                ->visible(fn(Get $get): bool => $get('tipo_abrangencia') === 'setor')
                                                ->required(fn(Get $get): bool => $get('tipo_abrangencia') === 'setor')
                                                ->afterStateHydrated(function (Select $component, ?InventarioUnidade $record): void {
                                                    if ($record && ! $record->inventariaUnidadeInteira()) {
                                                        $component->state($record->unidadePaiId());
                                                    }
                                                })
                                                ->afterStateUpdated(fn(Set $set) => $set('unidades', null))
                                                ->columnSpan(2),

                                            Select::make('unidades')
                                                ->label(fn(Get $get): string => $get('tipo_abrangencia') === 'setor' ? 'Setor' : 'Unidade Organizacional')
                                                ->options(fn(Get $get): array => self::opcoesAbrangencia($get))
                                                ->placeholder('Selecione a abrangência')
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->required()
                                                ->columnSpan(2),

                                            DatePicker::make('data_inicio')
                                                ->label('Data Início')
                                                ->displayFormat('d/m/Y')
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calcularDias($get, $set)),

                                            DatePicker::make('data_termino')
                                                ->label('Data Término')
                                                ->displayFormat('d/m/Y')
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn($state, Get $get, Set $set) => self::calcularDias($get, $set)),

                                            TextInput::make('dias')
                                                ->label('Dias')
                                                ->numeric()
                                                ->suffix('dias')
                                                ->readOnly()
                                                ->placeholder('Calculado automaticamente'),

                                            Select::make('situacao')
                                                ->label('Situação')
                                                ->options(Inventario::situacoes())
                                                ->native(false)
                                                ->default(Inventario::SITUACAO_A_INVENTARIAR),
                                        ]),
                                    ]),
                            ]),

                        Tab::make('Equipes de Campo')
                            ->icon('heroicon-m-users')
                            ->schema([
                                Repeater::make('equipes')
                                    ->relationship('equipes')
                                    ->schema([
                                        Grid::make(2)->schema([
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
                                                ->preload()
                                                ->native(false)
                                                ->required(),
                                        ]),
                                    ])
                                    ->defaultItems(0)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): string => $state['funcao'] ?? 'Integrante')
                                    ->addActionLabel('Adicionar Integrante'),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function calcularDias(Get $get, Set $set): void
    {
        $inicio = $get('data_inicio');
        $termino = $get('data_termino');

        if ($inicio && $termino) {
            $diff = Carbon::parse($inicio)->diffInDays(Carbon::parse($termino));
            $set('dias', $diff);
        }
    }

    private static function opcoesUnidadesOrganizacionais(): array
    {
        return Setores::query()
            ->unidadesInventariaveis()
            ->orderBy('setor')
            ->get(['id', 'UnidadeOrganizacional', 'Setor', 'CodigoPai'])
            ->mapWithKeys(fn(Setores $setor): array => [
                $setor->id => $setor->rotuloInventario(),
            ])
            ->toArray();
    }

    private static function opcoesAbrangencia(Get $get): array
    {
        if ($get('tipo_abrangencia') !== 'setor') {
            return self::opcoesUnidadesOrganizacionais();
        }

        $unidadePai = (int) $get('unidade_pai');

        if (! $unidadePai) {
            return [];
        }

        return Setores::query()
            ->filhosDe($unidadePai)
            ->orderBy('setor')
            ->pluck('setor', 'id')
            ->toArray();
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('unidade_inventariada', 'Unidade/Setor', isFirstColumn: true)
                    ->state(fn(InventarioUnidade $record): string => $record->rotuloUnidadeInventariada())
                    ->icon('heroicon-o-building-office')
                    ->weight('medium')
                    ->wrap(),
                TableColumns::date('data_inicio', 'Data Início'),
                TableColumns::date('data_termino', 'Data Término'),
                TableColumns::text('dias', 'Dias')
                    ->suffix(' dias'),
                TableColumns::text('situacao', 'Situação')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Concluído', 'carga efetuada' => 'success',
                        'Em andamento' => 'info',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn(?string $state): string => Inventario::rotuloSituacao($state))
                    ->color(fn(?string $state): string => Inventario::corSituacao($state)),

                TableColumns::text('equipes_integrantes', 'Integrantes')
                    ->counts('equipes')
                    ->state(fn(InventarioUnidade $record): int => (int) ($record->equipes_count ?? $record->equipes()->count()))
                    ->searchable(false)
                    ->sortable(false)
                    ->badge()
                    ->color('gray')
                    ->weight('medium')
                    ->icon('heroicon-o-users')
                    ->iconColor('primary')
                    ->tooltip('Visualizar integrantes')
                    ->action(self::equipesIntegrantesTableAction()),
            ]);
    }

    private static function equipesIntegrantesTableAction(): Action
    {
        return TableModalAction::make('visualizar_integrantes_equipe')
            ->modalHeading(fn(InventarioUnidade $record): string => sprintf(
                'Integrantes da unidade %s',
                $record->unidade?->setor ?? $record->unidade?->unidadeorganizacional ?? $record->unidades
            ))
            ->modalContent(fn(InventarioUnidade $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.inventario-equipes-modal',
                    [
                        'inventarioId' => (int) $record->id_inventario,
                        'unidadeId' => (int) $record->getKey(),
                    ],
                    "inventario-equipes-{$record->getKey()}",
                )
            ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventarioUnidades::route('/'),
            'create' => CreateInventarioUnidade::route('/create'),
            'edit' => EditInventarioUnidade::route('/{record}/edit'),
        ];
    }
}
