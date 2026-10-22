<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource\Pages\CreateDepreciacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource\Pages\EditDepreciacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource\Pages\ListDepreciacaos;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DepreciacaoResource\Widgets\DepreciacaoValorChart;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DepreciacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Depreciacao::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationLabel = 'Depreciação';

    protected static ?string $modelLabel = 'Depreciação de Imóvel';

    protected static ?string $pluralModelLabel = 'Depreciações de Imóveis';

    protected static string|\UnitEnum|null $navigationGroup = 'Bens Imóveis';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'bens-imoveis/depreciacao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação do Imóvel')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Select::make('Id_imovel')
                            ->label('Imóvel')
                            ->relationship('imovelRelacaoref', 'descricao')
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecione o imóvel')
                            ->columnSpanFull(),

                        Select::make('id_obra')
                            ->label('Obras e Ampliações')
                            ->relationship('obraRelacaoref', 'descricao')
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecione a obra ou ampliação')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Parâmetros do Cálculo')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        DatePicker::make('data_calculo')
                            ->label('Data do Cálculo')
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        DateTimePicker::make('date_time')
                            ->label('Atualizado em')
                            ->displayFormat('d/m/Y H:i')
                            ->native(false),

                        TextInput::make('vida_util')
                            ->label('Vida Útil')
                            ->numeric()
                            ->suffix('meses')
                            ->placeholder('0'),
                    ])
                    ->columns(3),

                Section::make('Valores da Depreciação')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        MoneyInput::make('depreciacao_mensal')
                            ->label('Depreciação Mensal'),
                        MoneyInput::make('depreciacao_acumulada')
                            ->label('Depreciação Acumulada'),
                        MoneyInput::make('valor_residual')
                            ->label('Valor Residual'),
                        MoneyInput::make('valor_liquido_contabil')
                            ->label('Valor Líquido Contábil'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn($query) => $query->with(['imovelRelacaoref', 'obraRelacaoref']))
            ->emptyStateIcon('heroicon-o-funnel')
            ->emptyStateHeading(fn($livewire): string => filled(data_get($livewire, 'tableFilters.Id_imovel.value'))
                ? 'Nenhuma depreciação encontrada'
                : 'Selecione um imóvel no filtro')
            ->emptyStateDescription(fn($livewire): string => filled(data_get($livewire, 'tableFilters.Id_imovel.value'))
                ? 'Não há registros de depreciação para o imóvel selecionado.'
                : 'A tabela de depreciações só carrega registros após a busca por um imóvel.')
            ->columns([
                TableColumns::text('Id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('imovelRelacaoref.descricao', 'Descrição do Imóvel'),
                TableColumns::text('obraRelacaoref.descricao', 'Obras e Ampliações'),
                TableColumns::money('depreciacao_acumulada', 'Depreciação Acumulada'),
                TableColumns::money('valor_residual', 'Valor Residual'),
                TableColumns::money('valor', 'Valor'),
                TableColumns::money('valor_liquido_contabil', 'Valor Contábil Líquido'),
                TableColumns::text('vida_util')
                    ->label(new HtmlString('Vida Útil<br>(em meses)'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('Id_imovel')
                    ->label('Imóvel')
                    ->columnSpan(2)
                    ->relationship('imovelRelacaoref', 'descricao')
                    ->searchable()
                    ->preload()
                    ->query(fn($query, array $data) => $query->when(
                        filled($data['value'] ?? null),
                        fn($query) => $query->where('Id_imovel', $data['value']),
                        fn($query) => $query->whereKey(-1),
                    )),
            ]);
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
