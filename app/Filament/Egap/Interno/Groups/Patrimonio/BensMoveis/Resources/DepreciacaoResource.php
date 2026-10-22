<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource\Pages\CreateDepreciacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource\Pages\EditDepreciacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource\Pages\ListDepreciacaos;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\DepreciacaoResource\Widgets\DepreciacaoValorChart;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Depreciacao;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DepreciacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Depreciacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Depreciação';

    protected static ?string $modelLabel = 'Depreciação';

    protected static ?string $pluralModelLabel = 'Depreciações';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'item';

    protected static ?string $slug = 'bens-moveis/depreciacoes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação e Período')
                    ->description('Vincule o bem móvel e informe a referência do cálculo.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Select::make('patrimonio')
                            ->label('Patrimônio')
                            ->relationship('patrimonioRef', 'NumPatrimonio')
                            ->getOptionLabelFromRecordUsing(fn(BemMovel $record): string => "{$record->NumPatrimonio} - {$record->Descricao}")
                            ->placeholder('Busque pelo número do patrimônio')
                            ->searchable()
                            ->native(false)
                            ->optionsLimit(50)
                            ->required()
                            ->columnSpanFull(),

                        DatePicker::make('data_calculo')
                            ->label('Data do Cálculo')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->default(now())
                            ->required(),

                        TextInput::make('item')
                            ->label('Item')
                            ->placeholder('Informe a referência do cálculo')
                            ->maxLength(255)
                            ->required(),

                        TextInput::make('vida_util')
                            ->label('Vida Útil')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('meses')
                            ->placeholder('0')
                            ->required(),
                    ])->columns(2),

                Section::make('Valores da Depreciação')
                    ->description('Valores financeiros correspondentes a este período.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        MoneyInput::make('valor')
                            ->label('Valor Base')
                            ->required(),

                        MoneyInput::make('valor_residual')
                            ->label('Valor Residual')
                            ->required(),

                        MoneyInput::make('depreciacao_mensal')
                            ->label('Depreciação Mensal')
                            ->required(),

                        MoneyInput::make('depreciacao_acumulada')
                            ->label('Depreciação Acumulada')
                            ->required(),

                        MoneyInput::make('valor_liquido_contabil')
                            ->label('Valor Líquido Contábil')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn($query) => $query->with('patrimonioRef'))
            ->emptyStateIcon('heroicon-o-funnel')
            ->emptyStateHeading(fn($livewire): string => filled(data_get($livewire, 'tableFilters.patrimonio.patrimonio'))
                ? 'Nenhuma depreciação encontrada'
                : 'Selecione um patrimônio no filtro')
            ->emptyStateDescription(fn($livewire): string => filled(data_get($livewire, 'tableFilters.patrimonio.patrimonio'))
                ? 'Não há registros de depreciação para o patrimônio selecionado.'
                : 'A tabela de depreciações só carrega registros após a busca por um patrimônio.')
            ->columns([
                TableColumns::text('patrimonioRef.NumPatrimonio', 'Patrimônio', true)
                    ->badge()
                    ->copyable()
                    ->copyMessage('Patrimônio copiado')
                    ->weight('medium'),
                TableColumns::text('patrimonioRef.Descricao', 'Descrição')
                    ->limit(30)
                    ->tooltip(fn(Depreciacao $record): ?string => $record->patrimonioRef?->Descricao),
                TableColumns::text('item', 'Item')
                    ->limit(35)
                    ->tooltip(fn(Depreciacao $record): ?string => $record->item),
                TableColumns::date('data_calculo', 'Data do Cálculo')
                    ->badge()
                    ->color('primary'),
                TableColumns::money('valor', 'Valor Base'),
                TableColumns::money('valor_residual', 'Valor Residual'),
                TableColumns::money('depreciacao_mensal', 'Depreciação Mensal')
                    ->weight('medium'),
                TableColumns::money('depreciacao_acumulada', 'Depreciação Acumulada'),
                TableColumns::money('valor_liquido_contabil', 'Valor Líquido Contábil')
                    ->weight('medium'),
                TableColumns::text('vida_util')
                    ->label(new HtmlString('Vida Útil<br>(em meses)'))
                    ->badge(),
            ])
            ->filters([
                Filter::make('patrimonio')
                    ->columnSpan(2)
                    ->label('Patrimônio')
                    ->schema([
                        Select::make('patrimonio')
                            ->label('Patrimônio')
                            ->placeholder('Busque pelo número do patrimônio')
                            ->getSearchResultsUsing(fn(string $search): array => BemMovel::query()
                                ->where('NumPatrimonio', 'like', "%{$search}%")
                                ->orWhere('Descricao', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn(BemMovel $record): array => [
                                    $record->getKey() => "{$record->NumPatrimonio} - {$record->Descricao}",
                                ])
                                ->all())
                            ->getOptionLabelUsing(function ($value): ?string {
                                $record = BemMovel::query()->find($value);

                                return $record
                                    ? "{$record->NumPatrimonio} - {$record->Descricao}"
                                    : null;
                            })
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn($query, array $data) => $query
                        ->when(
                            filled($data['patrimonio'] ?? null),
                            fn($query) => $query->where('patrimonio', $data['patrimonio']),
                            fn($query) => $query->whereKey(-1),
                        )),
            ])
            ->defaultSort('item', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepreciacaos::route('/'),
            'create' => CreateDepreciacao::route('/create'),
            'edit' => EditDepreciacao::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            DepreciacaoValorChart::class,
        ];
    }
}
