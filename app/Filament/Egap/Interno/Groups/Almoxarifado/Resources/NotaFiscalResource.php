<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources;

use App\Filament\Egap\Interno\Clusters\AlmoxarifadoCluster;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource\Pages\CreateNotaFiscal;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource\Pages\EditNotaFiscal;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\NotaFiscalResource\Pages\ListNotaFiscals;
use App\Filament\Egap\Interno\Permissions\Almoxarifado\AlmoxarifadoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\NotaFiscal;
use App\Models\egap\Cadastro\Setores;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NotaFiscalResource extends AlmoxarifadoResource
{
    protected static ?string $model = NotaFiscal::class;

    protected static ?string $slug = 'notas-fiscais';

    protected static ?string $cluster = AlmoxarifadoCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Nota Fiscal';

    protected static ?string $pluralModelLabel = 'Notas Fiscais';

    protected static ?string $navigationLabel = 'Nota Fiscal';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('TabsNotaFiscal')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Dados Gerais')
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('num_documento')
                                        ->numeric()
                                        ->required()
                                        ->label('Núm. Documento'),

                                    Select::make('tipo_documento')
                                        ->label('Tipo Documento')
                                        ->required()
                                        ->relationship('tipoDocumento', 'descricao')
                                        ->searchable()
                                        ->preload()
                                        ->native(false),

                                    DatePicker::make('data_documento')
                                        ->label('Data Documento')
                                        ->default(now())
                                        ->required()
                                        ->displayFormat('d/m/Y')
                                        ->native(false),
                                ]),

                            Grid::make(3)
                                ->schema([
                                    Select::make('fornecedor')
                                        ->label('Fornecedor')
                                        ->required()
                                        ->relationship('fornecedorRef', 'NomeFornecedor')
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->columnSpan(2),

                                    Select::make('estoque')
                                        ->label('Estoque')
                                        ->required()
                                        ->options([
                                            '1' => 'Entrada no Estoque',
                                            '2' => 'Saída Imediata',
                                        ])
                                        ->native(false),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Select::make('unidade_judiciaria')
                                        ->label('Unidade Judiciária')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->options(
                                            fn() => Setores::query()
                                                ->whereColumn('id', 'codigodauo')
                                                ->orderBy('unidadeorganizacional')
                                                ->pluck('unidadeorganizacional', 'codigopai')
                                                ->toArray()
                                        )
                                        ->afterStateUpdated(fn(Set $set) => $set('setor', null)),

                                    Select::make('setor')
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
                                ]),

                            Textarea::make('observacao')
                                ->label('Observação')
                                ->required()
                                ->columnSpanFull()
                                ->rows(3),
                        ]),

                    Tab::make('Itens da Nota')
                        ->icon('heroicon-m-shopping-cart')
                        ->schema([

                            TextInput::make('valor_total')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated()
                                ->default(0)
                                ->prefix('R$')
                                ->label('Subtotal / Total da Nota Fiscal'),

                            Repeater::make('itens')
                                ->relationship('itens')
                                ->label('Itens da Nota')
                                ->addActionLabel('Adicionar novo material')
                                ->columns(12)
                                ->live()
                                ->schema([
                                    Select::make('id_material')
                                        ->label('Material')
                                        ->relationship('material', 'descricao_detalhada')
                                        ->searchable()
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(6),

                                    TextInput::make('quantidade')
                                        ->label('Quantidade')
                                        ->required()
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::atualizarTotalItem($get, $set);
                                            self::atualizarTotalNota($get, $set);
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('preco_unitario')
                                        ->label('Preço Unitário')
                                        ->required()
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->prefix('R$')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::atualizarTotalItem($get, $set);
                                            self::atualizarTotalNota($get, $set);
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('total_item')
                                        ->label('Total Item')
                                        ->numeric()
                                        ->readOnly()
                                        ->dehydrated()
                                        ->prefix('R$')
                                        ->columnSpan(2),

                                ])
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::atualizarTotalNota($get, $set))
                                ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): array => self::mutateItemData($data))
                                ->mutateRelationshipDataBeforeSaveUsing(fn(array $data): array => self::mutateItemData($data))
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Situação')
                        ->icon('heroicon-m-check-badge')
                        ->schema([
                            Grid::make(2)
                                ->schema([

                                    Select::make('situacao')
                                        ->label('Situação')
                                        ->required()
                                        ->relationship('situacaoRef', 'descricao')
                                        ->native(false),

                                    DatePicker::make('data_situacao')
                                        ->label('Data da Situação')
                                        ->required()
                                        ->displayFormat('d/m/Y')
                                        ->native(false),

                                ]),
                        ]),
                ]),
        ]);
    }

    public static function mutateItemData(array $data): array
    {
        $quantidade = self::normalizarValorMonetario($data['quantidade'] ?? 0);
        $precoUnitario = self::normalizarValorMonetario($data['preco_unitario'] ?? 0);

        $data['quantidade'] = number_format($quantidade, 2, '.', '');
        $data['preco_unitario'] = number_format($precoUnitario, 2, '.', '');
        $data['total_item'] = number_format($quantidade * $precoUnitario, 2, '.', '');
        $data['atualizado_por'] = auth()->id();
        $data['date_time'] = now();

        return $data;
    }

    public static function atualizarTotalItem(Get $get, Set $set): void
    {
        $quantidade = self::normalizarValorMonetario($get('quantidade'));
        $precoUnitario = self::normalizarValorMonetario($get('preco_unitario'));

        $set('total_item', number_format($quantidade * $precoUnitario, 2, '.', ''));
    }

    public static function atualizarTotalNota(Get $get, Set $set): void
    {
        $itens = $get('../../itens') ?? [];

        $totalGeral = collect($itens)
            ->sum(fn(array $item) => self::normalizarValorMonetario($item['total_item'] ?? 0));

        $set('../../valor_total', number_format($totalGeral, 2, '.', ''));
    }

    public static function calcularValorTotal(array $itens): string
    {
        $total = collect($itens)
            ->sum(
                fn(array $item) => self::normalizarValorMonetario($item['quantidade'] ?? 0) *
                    self::normalizarValorMonetario($item['preco_unitario'] ?? 0)
            );

        return number_format($total, 2, '.', '');
    }

    public static function normalizarValorMonetario(float|int|string|null $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        $valor = preg_replace('/[^\d,.-]/', '', (string) $valor) ?? '0';

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('num_documento', 'Núm. documento', isFirstColumn: true),

                TableColumns::date('data_documento', 'Data documento'),

                TableColumns::text('fornecedorRef.NomeFornecedor', 'Fornecedor'),

                TableColumns::money('valor_total', 'Total da Nota', divideBy: true),

                TableColumns::text('itens.material.descricao_detalhada', 'Material')
                    ->state(
                        fn($record) => $record->itens
                            ->pluck('material.descricao_detalhada')
                            ->toArray()
                    )
                    ->listWithLineBreaks()
                    ->wrap()
                    ->limit(40),

                TableColumns::text('itens.quantidade', 'Quantidade')
                    ->state(fn(NotaFiscal $record): array => $record->itens->pluck('quantidade')->toArray())
                    ->listWithLineBreaks(),

                TableColumns::text('itens.preco_unitario', 'Preço unitário')
                    ->state(fn(NotaFiscal $record): array => $record->itens
                        ->map(fn($item) => 'R$ ' . number_format((float) $item->preco_unitario, 2, ',', '.'))
                        ->toArray())
                    ->listWithLineBreaks(),

                TableColumns::text('total_item_calculado', 'Total item')
                    ->state(fn(NotaFiscal $record): array => $record->itens
                        ->map(fn($item) => 'R$ ' . number_format((float) $item->preco_unitario * (float) $item->quantidade, 2, ',', '.'))
                        ->toArray())
                    ->listWithLineBreaks(),

                TableColumns::text('situacaoRef.descricao', 'Situação'),
            ])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotaFiscals::route('/'),
            'create' => CreateNotaFiscal::route('/create'),
            'edit' => EditNotaFiscal::route('/{record}/edit'),
        ];
    }

}
