<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource\Pages\CreateBemMovel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource\Pages\EditBemMovel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource\Pages\ListBemMovels;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource\Widgets\BensMoveisCountStats;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\ComplementoSetor;
use App\Models\egap\Cadastro\ElementoDespesa;
use App\Models\egap\Cadastro\Modelos;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Cadastro\UnidadesDeMedida;
use App\Models\egap\Patrimonio\BensMoveis\Baixa;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\ItemBaixa;
use App\Models\egap\Patrimonio\BensMoveis\SituacaoBemMovel;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use App\Services\Egap\Patrimonio\RecalcularDepreciacaoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Throwable;

class BemMovelResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = BemMovel::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Administração';

    protected static ?string $modelLabel = 'Bem Móvel';

    protected static ?string $pluralModelLabel = 'Administração dos Bens Móveis';

    protected static ?string $recordTitleAttribute = 'NumPatrimonio';

    protected static ?string $slug = 'bens-moveis/adm-bens-moveis';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('TabsBemMovel')
                ->tabs([
                    self::tabIdentificacao(),
                    self::tabSituacao(),
                    self::tabLocalizacao(),
                    self::tabAquisicao(),
                    self::tabValores(),
                    self::tabObservacoes(),
                ])
                ->columnSpanFull(),
        ]);
    }

    // ---- Tabs ----------------------------------------------------------------

    private static function tabIdentificacao(): Tab
    {
        return Tab::make('Identificação')
            ->icon('heroicon-o-identification')
            ->schema([
                Section::make('Identificação do Bem')
                    ->description('Números de controle patrimonial que identificam o bem.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('NumPatrimonio')
                            ->label('Nº Patrimônio')
                            ->placeholder('Informe o número do patrimônio')
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('NumerodePatAnterior')
                            ->label('Nº Patrimônio Anterior')
                            ->placeholder('Informe o número anterior, se houver')
                            ->columnSpan(3),

                        TextInput::make('NumerodeSerie')
                            ->label('Número de Série')
                            ->placeholder('Informe o número de série')
                            ->columnSpan(3),

                        TextInput::make('TomboSmarapd')
                            ->label('Tombo Smarapd')
                            ->placeholder('Informe o tombo Smarapd')
                            ->columnSpan(3),

                        TextInput::make('NumTomboSmarapd')
                            ->label('Nº do Tombo Smarapd')
                            ->numeric()
                            ->placeholder('Informe o número do tombo')
                            ->columnSpan(4),

                        Select::make('Unidade')
                            ->label('Unidade de Medida')
                            ->relationship('unidadeMedidaRef', 'Unidade')
                            ->getOptionLabelFromRecordUsing(fn(UnidadesDeMedida $record): string => filled($record->Sigla)
                                ? "{$record->Sigla} - {$record->Unidade}"
                                : $record->Unidade)
                            ->searchable(['Sigla', 'Unidade'])
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a unidade de medida')
                            ->columnSpan(8),
                    ])
                    ->columns(12),

                Section::make('Descrição e Classificação')
                    ->description('Descrição detalhada e características do bem.')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Select::make('DescricaoResumidadoBem')
                            ->label('Descrição Resumida')
                            ->relationship('descricaoResumidaBemRef', 'Descricao')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a descrição resumida')
                            ->columnSpan(6),

                        Select::make('id_descricaodetalhada')
                            ->label('Descrição Detalhada')
                            ->relationship('descricaoDetalhadaRef', 'descricao_detalhada')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a descrição detalhada')
                            ->columnSpan(6),

                        Select::make('Marca')
                            ->label('Marca')
                            ->relationship('marcaRef', 'descricao')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('Modelo', null))
                            ->placeholder('Selecione a marca')
                            ->columnSpan(4),

                        Select::make('Modelo')
                            ->label('Modelo')
                            ->placeholder(
                                fn(Get $get) => blank($get('Marca'))
                                    ? 'Selecione primeiro a marca'
                                    : 'Selecione o modelo'
                            )
                            ->options(
                                fn(Get $get): array => Modelos::query()
                                    ->when(
                                        $get('Marca'),
                                        fn($query, $marca) => $query->where('marca', $marca)
                                    )
                                    ->orderBy('descricao')
                                    ->pluck('descricao', 'id')
                                    ->toArray()
                            )
                            ->disabled(fn(Get $get) => blank($get('Marca')))
                            ->searchable()
                            ->native(false)
                            ->columnSpan(4),

                        TextInput::make('TipodoBem')
                            ->label('Tipo do Bem')
                            ->placeholder('Informe o tipo do bem')
                            ->columnSpan(4),

                        TextInput::make('EstadodeConservacao')
                            ->label('Estado de Conservação')
                            ->placeholder('Informe o estado de conservação')
                            ->columnSpan(3),

                        TextInput::make('Voltagem')
                            ->placeholder('Informe a voltagem')
                            ->columnSpan(3),

                        TextInput::make('Categoria')
                            ->placeholder('Informe a categoria')
                            ->columnSpan(3),

                        TextInput::make('Combustivel')
                            ->placeholder('Informe o combustível')
                            ->columnSpan(3),

                        TextInput::make('ClassificacaoInservivel')
                            ->label('Classificação de Inservível')
                            ->placeholder('Informe a classificação, se inservível')
                            ->columnSpanFull(),

                        Textarea::make('Descricao')
                            ->label('Descrição Detalhada do Bem')
                            ->placeholder('Descreva o bem detalhadamente')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
            ]);
    }

    private static function tabSituacao(): Tab
    {
        return Tab::make('Situação')
            ->icon('heroicon-o-shield-check')
            ->schema([
                Section::make('Situação e Identificação Veicular')
                    ->description('Situação atual do bem e dados de identificação, quando se tratar de veículo.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('SituacaoBem')
                            ->label('Situação do Bem')
                            ->relationship('situacaoBemRef', 'descricao')
                            ->getOptionLabelFromRecordUsing(fn(SituacaoBemMovel $record): string => $record->descricao_completa)
                            ->searchable(['descricao', 'situacao'])
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a situação do bem')
                            ->columnSpan(6),

                        TextInput::make('Placa')
                            ->maxLength(8)
                            ->placeholder('Informe a placa')
                            ->columnSpan(2),

                        TextInput::make('Chassi')
                            ->placeholder('Informe o chassi')
                            ->columnSpan(4),

                        TextInput::make('Renavam')
                            ->placeholder('Informe o Renavam')
                            ->columnSpan(4),

                        TextInput::make('AnoFabricacao')
                            ->label('Ano de Fabricação')
                            ->numeric()
                            ->maxLength(4)
                            ->placeholder('AAAA')
                            ->columnSpan(4),

                        TextInput::make('AnoModelo')
                            ->label('Ano do Modelo')
                            ->numeric()
                            ->maxLength(4)
                            ->placeholder('AAAA')
                            ->columnSpan(4),
                    ])
                    ->columns(12),
            ]);
    }

    private static function tabLocalizacao(): Tab
    {
        return Tab::make('Localização')
            ->icon('heroicon-o-building-office-2')
            ->schema([
                Section::make('Localização do Bem')
                    ->description('Unidade judiciária, setor e localização física atual do bem.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Select::make('UnidadeJudiciaria')
                            ->label('Unidade Judiciária')
                            ->placeholder('Selecione a unidade judiciária')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->native(false)
                            ->options(
                                fn() => Setores::query()
                                    ->whereColumn('id', 'codigodauo')
                                    ->orderBy('unidadeorganizacional')
                                    ->pluck('unidadeorganizacional', 'codigopai')
                                    ->toArray()
                            )
                            ->afterStateUpdated(fn(Set $set) => $set('Setor', null))
                            ->columnSpan(6),

                        Select::make('Setor')
                            ->placeholder(
                                fn(Get $get) => blank($get('UnidadeJudiciaria'))
                                    ? 'Selecione primeiro a unidade judiciária'
                                    : 'Selecione o setor'
                            )
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(
                                fn(Get $get) => Setores::query()
                                    ->when(
                                        $get('UnidadeJudiciaria'),
                                        fn($query, $codigoPai) => $query->where('codigopai', $codigoPai)
                                    )
                                    ->orderBy('setor')
                                    ->pluck('setor', 'id')
                                    ->toArray()
                            )
                            ->disabled(fn(Get $get) => blank($get('UnidadeJudiciaria')))
                            ->columnSpan(6),

                        Select::make('ComplementoSetor')
                            ->label('Complemento')
                            ->relationship('complementoSetorRef', 'descricao')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o complemento')
                            ->columnSpan(8),

                        Select::make('AndarSetor')
                            ->label('Andar')
                            ->placeholder('Informe o andar')
                            ->options(self::andaresOptions())
                            ->native(false)
                            ->columnSpan(4),
                    ])
                    ->columns(12),
            ]);
    }

    private static function tabAquisicao(): Tab
    {
        return Tab::make('Aquisição')
            ->icon('heroicon-o-shopping-cart')
            ->schema([
                Section::make('Processo de Aquisição')
                    ->description('Elemento de despesa, fornecedor e documentos fiscais vinculados.')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Select::make('Produto')
                            ->label('Elemento de Despesa')
                            ->relationship('produtoRef', 'CodigodaClasse')
                            ->getOptionLabelFromRecordUsing(fn(ElementoDespesa $record): string => filled($record->DescricaodaClasse)
                                ? "{$record->CodigodaClasse} - {$record->DescricaodaClasse}"
                                : (string) $record->CodigodaClasse)
                            ->searchable(['CodigodaClasse', 'DescricaodaClasse'])
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o elemento de despesa')
                            ->columnSpan(6),

                        Select::make('ContaContabil')
                            ->label('Conta Contábil')
                            ->relationship('contaContabilRef', 'titulo')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a conta contábil')
                            ->columnSpan(6),

                        Select::make('Fornecedor')
                            ->relationship('fornecedorRef', 'NomeFornecedor')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o fornecedor')
                            ->columnSpan(6),

                        Select::make('NotaFiscal')
                            ->label('Nota Fiscal')
                            ->relationship('notaFiscalRef', 'num_documento')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione a nota fiscal')
                            ->columnSpan(6),

                        TextInput::make('FormaAquisicao')
                            ->label('Forma de Aquisição')
                            ->placeholder('Informe a forma de aquisição')
                            ->columnSpan(4),

                        TextInput::make('numero_processo')
                            ->label('Número do Processo')
                            ->placeholder('Informe o número do processo')
                            ->columnSpan(4),

                        TextInput::make('nota_empenho')
                            ->label('Nota de Empenho')
                            ->placeholder('Informe a nota de empenho')
                            ->columnSpan(4),

                        TextInput::make('nota_liquidacao')
                            ->label('Nota de Liquidação')
                            ->placeholder('Informe a nota de liquidação')
                            ->columnSpan(6),

                        DatePicker::make('data_liquidacao')
                            ->label('Data da Liquidação')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->columnSpan(6),
                    ])
                    ->columns(12),

                Section::make('Dados Complementares')
                    ->description('Unidade gestora, numeração de lote e demais dados de controle.')
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        TextInput::make('SiglaUnidadeGestora')
                            ->label('Sigla da Unidade Gestora')
                            ->placeholder('Informe a sigla da unidade gestora')
                            ->columnSpan(6),

                        TextInput::make('unidade_gestora')
                            ->label('Unidade Gestora')
                            ->placeholder('Informe a unidade gestora')
                            ->columnSpan(6),

                        TextInput::make('Lote')
                            ->numeric()
                            ->placeholder('Informe o lote')
                            ->columnSpan(4),

                        TextInput::make('NumeracaoInicial')
                            ->label('Numeração Inicial')
                            ->placeholder('Informe a numeração inicial')
                            ->columnSpan(4),

                        TextInput::make('NumeracaoFinal')
                            ->label('Numeração Final')
                            ->placeholder('Informe a numeração final')
                            ->columnSpan(4),

                        TextInput::make('MesdeReferencia')
                            ->label('Mês de Referência')
                            ->placeholder('Informe o mês de referência')
                            ->columnSpan(4),

                        TextInput::make('Garantia')
                            ->placeholder('Informe a garantia')
                            ->columnSpan(4),

                        DatePicker::make('DatadeVencimento')
                            ->label('Data de Vencimento')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->columnSpan(4),
                    ])
                    ->columns(12),
            ]);
    }

    private static function tabValores(): Tab
    {
        return Tab::make('Valores')
            ->icon('heroicon-o-banknotes')
            ->schema([
                Section::make('Valores e Depreciação')
                    ->description('Valores de aquisição e reavaliação utilizados no cálculo de depreciação. Os valores contábeis atuais são atualizados automaticamente pela ação "Gerar Novo Cálculo".')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        MoneyInput::make('ValorAquisicao')
                            ->label('Valor de Aquisição')
                            ->required()
                            ->columnSpan(6),

                        MoneyInput::make('ValordeMercado')
                            ->label('Valor de Mercado')
                            ->columnSpan(6),

                        DatePicker::make('DatadeIncorporacao')
                            ->label('Data de Incorporação')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->columnSpan(4),

                        DatePicker::make('DataDisponibilizacao')
                            ->label('Data da Disponibilização')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->columnSpan(4),

                        DatePicker::make('DatadaReavaliacao')
                            ->label('Data da Reavaliação')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->closeOnDateSelection()
                            ->columnSpan(4),

                        MoneyInput::make('ValordaReavaliacao')
                            ->label('Valor da Reavaliação')
                            ->columnSpan(6),

                        TextInput::make('VidaUtilReavaliacao')
                            ->label('Vida Útil da Reavaliação')
                            ->numeric()
                            ->suffix('meses')
                            ->placeholder('0')
                            ->columnSpan(6),
                    ])
                    ->columns(12),
            ]);
    }

    private static function tabObservacoes(): Tab
    {
        return Tab::make('Observações')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->schema([
                Section::make('Observações')
                    ->description('Adicione informações complementares sobre o bem, se necessário.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Textarea::make('Observacao')
                            ->label('Observação')
                            ->rows(5)
                            ->placeholder('Digite aqui alguma observação ou informação adicional...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function andaresOptions(): array
    {
        return [
            'SUB-SOLO' => 'Sub-solo',
            'TERREO' => 'Térreo',
            '1' => '1º andar',
            '2' => '2º andar',
            '3' => '3º andar',
            '4' => '4º andar',
            '5' => '5º andar',
            '6' => '6º andar',
            '7' => '7º andar',
            '8' => '8º andar',
            '9' => '9º andar',
            '10' => '10º andar',
            '11' => '11º andar',
            '12' => '12º andar',
            '13' => '13º andar',
            '14' => '14º andar',
            '15' => '15º andar',
            '16' => '16º andar',
            '17' => '17º andar',
            '18' => '18º andar',
            '19' => '19º andar',
            '20' => '20º andar',
        ];
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with('ultimaTransferencia'))
            ->columns([
                TableColumns::text('NumPatrimonio', 'Patrimônio', true, true),
                TableColumns::text('NumerodeSerie', 'Número de Série'),
                TableColumns::text('Descricao', 'Descrição Detalhada'),
                TableColumns::text('modeloRef.descricao', 'Modelo'),
                TableColumns::text('unidadeJudiciariaRef.unidadeorganizacional', 'Unidade Judiciária'),
                TableColumns::text('setorRef.setor', 'Setor'),
                TableColumns::text('complementoSetorRef.descricao', 'Complemento do Setor'),
                TableColumns::money('Valor', 'Valor de Aquisição')
                    ->badge()
                    ->color('success'),
                TableColumns::date('DataDisponibilizacao', 'Data da Disponibilização'),
                TableColumns::date('DatadaReavaliacao', 'Data da Reavaliação'),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::imprimirTermoTableAction(),
                    self::transferirBensTableAction(),
                    self::historicoMovimentacoesTableAction(),
                    self::vincularBensTableAction(),
                    self::calculoDepreciacaoMensalTableAction(),
                    self::gerarNovoCalculoTableAction(),
                    self::conciliarBemTableAction(),
                    self::termoDigitalizadoTableAction(),
                    self::reavaliarBensTableAction(),
                    self::corrigirInformacoesTableAction(),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('Imprimir Bens')
                    ->icon('heroicon-o-printer'),
                ...TableDefaults::bulkActions(),
            ]);
    }

    private static function imprimirTermoTableAction(): Action
    {
        return Action::make('imprimir_termo')
            ->label('Imprimir Termo')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn(BemMovel $record): bool => filled($record->ultimaTransferencia?->Termo))
            ->url(fn(BemMovel $record): string => route('termo.imprimir', ['id' => $record->ultimaTransferencia->Termo]))
            ->openUrlInNewTab();
    }

    private static function transferirBensTableAction(): Action
    {
        return Action::make('transferir_bens')
            ->label('Transferir Bens')
            ->icon('heroicon-o-arrow-right-circle')
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading(fn(BemMovel $record): string => "Transferir Bem #{$record->NumPatrimonio}")
            ->modalSubmitActionLabel('Transferir')
            ->schema(fn(Schema $schema): Schema => $schema->columns(12)->components([
                Select::make('UnidadeJudiciaria')
                    ->label('Unidade Judiciária')
                    ->placeholder('Selecione a unidade judiciária')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->native(false)
                    ->options(
                        fn() => Setores::query()
                            ->whereColumn('id', 'codigopai')
                            ->orderBy('unidadeorganizacional')
                            ->pluck('unidadeorganizacional', 'codigopai')
                            ->toArray()
                    )
                    ->afterStateUpdated(fn(Set $set) => $set('Setor', null))
                    ->columnSpan(['default' => 12, 'md' => 6]),

                Select::make('Setor')
                    ->label('Setor')
                    ->placeholder(
                        fn(Get $get) => blank($get('UnidadeJudiciaria'))
                            ? 'Selecione primeiro a unidade judiciária'
                            : 'Selecione o setor'
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(
                        fn (Get $get) => blank($get('UnidadeJudiciaria'))
                            ? []
                            : Setores::query()
                                ->where('codigodauo', $get('UnidadeJudiciaria'))
                                ->whereColumn('id', '<>', 'codigopai')
                                ->orderBy('setor')
                                ->pluck('setor', 'id')
                                ->toArray()
                    )
                    ->disabled(fn(Get $get) => blank($get('UnidadeJudiciaria')))
                    ->columnSpan(['default' => 12, 'md' => 6]),

                Select::make('ComplementoSetor')
                    ->label('Complemento')
                    ->placeholder('Selecione o complemento')
                    ->options(
                        fn(): array => ComplementoSetor::query()
                            ->where('descricao', '!=', ' ')
                            ->orderBy('descricao')
                            ->pluck('descricao', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->columnSpan(['default' => 12, 'md' => 6]),

                Select::make('Andar')
                    ->label('Andar')
                    ->placeholder('Informe o andar')
                    ->options(self::andaresOptions())
                    ->native(false)
                    ->columnSpan(['default' => 12, 'md' => 2]),

                Select::make('PedidoNo')
                    ->label('Pedido Nº')
                    ->placeholder('Selecione o pedido relacionado')
                    ->options(
                        fn(): array => Pedidos::query()
                            ->orderByDesc('id')
                            ->limit(100)
                            ->get(['id', 'num_protocolo'])
                            ->mapWithKeys(fn(Pedidos $pedido): array => [
                                $pedido->id => filled($pedido->num_protocolo)
                                    ? "{$pedido->id} - Protocolo {$pedido->num_protocolo}"
                                    : (string) $pedido->id,
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->native(false)
                    ->columnSpan(['default' => 12, 'md' => 4]),

                Textarea::make('Observação')
                    ->label('')
                    ->placeholder('Digite aqui alguma observação ou informação adicional...')
                    ->rows(4)
                    ->columnSpanFull(),
            ]))
            ->action(function (array $data, BemMovel $record): void {
                try {
                    DB::transaction(function () use ($data, $record): void {
                        TransferenciaBemMovel::create([
                            'NumPatrimonio' => $record->NumPatrimonio,
                            'UnidadeAnterior' => $record->UnidadeJudiciaria,
                            'SetorAnterior' => $record->Setor,
                            'ComplementoAnterior' => $record->ComplementoSetor,
                            'AndarAnterior' => $record->AndarSetor,
                            'UnidadeAtual' => $data['UnidadeJudiciaria'],
                            'SetorAtual' => $data['Setor'],
                            'ComplementoAtual' => $data['ComplementoSetor'],
                            'AndarAtual' => $data['Andar'],
                            'pedido_no' => $data['PedidoNo'],
                        ]);

                        $record->update([
                            'UnidadeJudiciaria' => $data['UnidadeJudiciaria'],
                            'Setor' => $data['Setor'],
                            'ComplementoSetor' => $data['ComplementoSetor'],
                            'AndarSetor' => $data['Andar'],
                            'Observacao' => $data['Observacao'],
                        ]);
                    });
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível transferir o bem.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Bem transferido com sucesso.')
                    ->success()
                    ->send();
            });
    }

    private static function historicoMovimentacoesTableAction(): Action
    {
        return TableModalAction::make('historico_movimentacoes')
            ->label('Histórico de Movimentações')
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->modalHeading(fn(BemMovel $record): string => "Histórico de Movimentações #{$record->NumPatrimonio}")
            ->modalContent(fn(BemMovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.historico-movimentacoes-modal',
                    ['numPatrimonio' => (int) $record->NumPatrimonio],
                    "historico-movimentacoes-{$record->getKey()}",
                )
            ));
    }

    private static function vincularBensTableAction(): Action
    {
        return Action::make('vincular_bens')
            ->label('Vincular Bens')
            ->color('gray')
            ->icon('heroicon-o-plus')
            ->modalHeading(fn(BemMovel $record): string => "Vincular Bem #{$record->NumPatrimonio}")
            ->modalSubmitActionLabel('Vincular')
            ->fillForm(fn(BemMovel $record): array => [
                'Detalhes' => $record->Observacao,
            ])
            ->schema([
                Select::make('id_baixa')
                    ->label('Processo')
                    ->placeholder('Selecione o processo de baixa')
                    ->options(
                        fn(): array => Baixa::query()
                            ->whereNull('DataBaixa')
                            ->orderByDesc('id')
                            ->get(['id', 'NumeroProcesso', 'Requisitante'])
                            ->mapWithKeys(fn(Baixa $baixa): array => [
                                $baixa->id => "{$baixa->NumeroProcesso} - {$baixa->Requisitante}",
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->native(false)
                    ->required(),

                Select::make('id_situacao')
                    ->label('Tipo da Baixa')
                    ->placeholder('Selecione o tipo da baixa')
                    ->options(
                        fn(): array => SituacaoBemMovel::query()
                            ->orderBy('descricao')
                            ->pluck('descricao', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->native(false)
                    ->required(),

                Textarea::make('Detalhes')
                    ->label('Detalhes')
                    ->placeholder('Informe detalhes sobre a baixa deste bem')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, BemMovel $record): void {
                try {
                    DB::transaction(function () use ($data, $record): void {
                        ItemBaixa::create([
                            'id_baixa' => $data['id_baixa'],
                            'id_bem' => $record->id,
                            'id_situacao' => $data['id_situacao'],
                        ]);

                        $record->update([
                            'Observacao' => $data['Detalhes'],
                        ]);
                    });
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível vincular o bem.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Bem vinculado com sucesso.')
                    ->success()
                    ->send();
            });
    }

    private static function calculoDepreciacaoMensalTableAction(): Action
    {
        return TableModalAction::make('calculo_depreciacao_mensal')
            ->label('Cálculo de Depreciação Mensal')
            ->tooltip('Calcular Depreciação Mensal')
            ->icon('heroicon-o-bell')
            ->modalHeading(fn(BemMovel $record): string => "Cálculo de Depreciação Mensal #{$record->NumPatrimonio}")
            ->modalContent(fn(BemMovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.depreciacao-modal',
                    ['bemMovelId' => (int) $record->getKey()],
                    "depreciacao-{$record->getKey()}",
                )
            ));
    }

    private static function gerarNovoCalculoTableAction(): Action
    {
        return Action::make('gerar_novo_calculo')
            ->label('Gerar Novo Cálculo')
            ->icon('heroicon-o-calculator')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Gerar novo cálculo de depreciação')
            ->modalDescription('O histórico de depreciação deste bem será apagado e recalculado do zero, e os valores atuais do patrimônio serão atualizados de acordo com o mês corrente.')
            ->modalSubmitActionLabel('Gerar cálculo')
            ->action(function (BemMovel $record): void {
                try {
                    $resultado = app(RecalcularDepreciacaoService::class)->recalcular($record);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->title('Não foi possível gerar o cálculo.')
                        ->body(collect($exception->errors())->flatten()->implode(' '))
                        ->danger()
                        ->send();

                    return;
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Não foi possível gerar o cálculo.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Cálculo de depreciação gerado com sucesso.')
                    ->body("{$resultado->parcelasGeradas} parcela(s) geradas. Depreciação mensal: R$ " . number_format($resultado->depreciacaoMensal, 2, ',', '.'))
                    ->success()
                    ->send();
            });
    }

    private static function conciliarBemTableAction(): Action
    {
        return Action::make('conciliar_bem')
            ->label('Conciliar Bem')
            ->icon('heroicon-o-bars-arrow-down');
    }

    private static function termoDigitalizadoTableAction(): Action
    {
        return Action::make('termo_digitalizado')
            ->label('Termo Digitalizado')
            ->icon('heroicon-o-clipboard-document-check');
    }

    private static function reavaliarBensTableAction(): Action
    {
        return Action::make('reavaliar_bens')
            ->label('Reavaliar Bens')
            ->icon('heroicon-o-clipboard-document-check');
    }

    private static function corrigirInformacoesTableAction(): Action
    {
        return Action::make('corrigir_informacoes')
            ->label('Corrigir Informações')
            ->icon('heroicon-o-clipboard-document-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBemMovels::route('/'),
            'create' => CreateBemMovel::route('/create'),
            'edit' => EditBemMovel::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            BensMoveisCountStats::class,
        ];
    }
}
