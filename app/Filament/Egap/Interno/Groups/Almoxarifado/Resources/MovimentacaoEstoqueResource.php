<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources;

use App\Filament\Egap\Interno\Clusters\AlmoxarifadoCluster;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\MovimentacaoEstoqueResource\Pages\CreateMovimentacaoEstoque;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\MovimentacaoEstoqueResource\Pages\EditMovimentacaoEstoque;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\MovimentacaoEstoqueResource\Pages\ListMovimentacaoEstoques;
use App\Filament\Egap\Interno\Permissions\Almoxarifado\AlmoxarifadoResource;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use App\Models\egap\Almoxarifado\NotaFiscal;
use App\Models\egap\Almoxarifado\TipoMovimentacaoNotaFiscal;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\UserEgap;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use phpDocumentor\Reflection\PseudoTypes\True_;

class MovimentacaoEstoqueResource extends AlmoxarifadoResource
{
    protected static ?string $model = MovimentacaoEstoque::class;

    protected static ?string $slug = 'movimentacao-estoque';

    protected static ?string $cluster = AlmoxarifadoCluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Movimentação de Estoque';

    protected static ?string $modelLabel = 'Movimentação de Estoque';

    protected static ?string $pluralModelLabel = 'Movimentações de Estoque';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('TabsMovimentacaoEstoque')
                ->columnSpanFull()
                ->tabs([
                    self::tabDadosMovimentacao(),
                    self::tabMaterialValores(),
                    self::tabSaldoEstoque(),
                    self::tabResponsabilidadeDestino(),
                ]),
        ]);
    }

    private static function tabDadosMovimentacao(): Tab
    {
        return Tab::make('Dados da Movimentação')
            ->icon('heroicon-o-arrows-right-left')
            ->schema([
                DateTimePicker::make('date_time')
                    ->label('Data/Hora')
                    ->required()
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->default(now())
                    ->prefixIcon('heroicon-o-calendar'),

                Select::make('tipo_movimentacao')
                    ->label('Tipo de Movimentação')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('Selecione o tipo')
                    ->prefixIcon('heroicon-o-arrows-right-left')
                    ->options(
                        fn() => TipoMovimentacaoNotaFiscal::query()
                            ->orderBy('descricao')
                            ->pluck('descricao', 'id')
                            ->toArray()
                    ),

                Select::make('nota_fiscal')
                    ->label('Nota Fiscal')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('Selecione a nota fiscal')
                    ->prefixIcon('heroicon-o-document-text')
                    ->options(
                        fn() => NotaFiscal::query()
                            ->orderByDesc('id')
                            ->pluck('num_documento', 'id')
                            ->toArray()
                    ),
            ]);
    }

    private static function tabMaterialValores(): Tab
    {
        return Tab::make('Material e Valores')
            ->icon('heroicon-o-cube')
            ->schema([
                Select::make('material')
                    ->label('Material')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->placeholder('Busque pela descrição do material')
                    ->options(fn () => DescricaoDetalhada::query()
                        ->where('descricao_detalhada', '!=', ' ')
                        ->pluck('descricao_detalhada', 'id')
                        ->toArray())
                    ->columnSpanFull(),

                TextInput::make('quantidade')
                    ->label('Quantidade')
                    ->numeric()
                    ->columnSpan(1)
                    ->inputMode('decimal')
                    ->required(),

                MoneyInput::make('preco_unitario')
                    ->label('Preço Unitário')
                    ->required()
                    ->columnSpan(1)
                    ->placeholder('0,00'),

                MoneyInput::make('valor_total')
                    ->label('Valor Total')
                    ->required()
                    ->columnSpan(1)
                    ->placeholder('0,00'),
            ]);
    }

    private static function tabSaldoEstoque(): Tab
    {
        return Tab::make('Saldo em Estoque')
            ->icon('heroicon-o-chart-bar-square')
            ->schema([
                TextInput::make('quantidade_estoque')
                    ->label('Quantidade em Estoque')
                    ->required()
                    ->numeric()
                    ->inputMode('decimal')
                    ->placeholder('0,00')
                    ->prefixIcon('heroicon-o-archive-box'),

                MoneyInput::make('preco_medio_estoque')
                    ->label('Preço Médio Estoque')
                    ->required()
                    ->placeholder('0,00'),

                MoneyInput::make('valor_total_estoque')
                    ->label('Valor Total Estoque')
                    ->required()
                    ->placeholder('0,00'),
            ]);
    }

    private static function tabResponsabilidadeDestino(): Tab
    {
        return Tab::make('Responsabilidade e Destino')
            ->icon('heroicon-o-building-office-2')
            ->schema([
                Select::make('id_setor')
                    ->label('Setor')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('Selecione o setor')
                    ->prefixIcon('heroicon-o-map-pin')
                    ->options(
                        fn() => Setores::all()
                            ->pluck('setor', 'id')
                            ->toArray()
                    ),

                Placeholder::make('info_calculo')
                    ->label('Observação')
                    ->content('Os campos monetários e quantitativos podem ser usados para rastrear o histórico do estoque e o custo médio.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'desc')
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),

                TableColumns::text('tipoMovimentacaoRel.descricao', 'Tipo Mov.'),

                TableColumns::dateTime('date_time', 'Data', 'd/m/Y'),

                TableColumns::text('notaFiscal.num_documento', 'Nota Fiscal'),

                TableColumns::text('materialRel.descricao_detalhada', 'Material'),

                TableColumns::text('quantidade', 'Qtd.'),

                TableColumns::money('preco_unitario', 'Preço Unit.', divideBy: true),

                TableColumns::money('valor_total', 'Valor Total', divideBy: true),

                TableColumns::text('quantidade_estoque', 'Qtd. Estoque'),

                TableColumns::money('preco_medio_estoque', 'Preço Médio', divideBy: true),

                TableColumns::money('valor_total_estoque', 'Total Estoque', divideBy: true),

                TableColumns::text('setor.unidadeorganizacional', 'Unidade Judiciária'),

                TableColumns::text('setor.setor', 'Setor'),

                TableColumns::text('pedido.id', 'Pedido', isIdColumn: true),

                TableColumns::updatedBy('atualizadoPor.name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMovimentacaoEstoques::route('/'),
        ];
    }
}
