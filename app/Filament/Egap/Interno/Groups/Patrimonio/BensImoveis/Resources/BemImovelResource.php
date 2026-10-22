<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Pages\CreateBemImovel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Pages\EditBemImovel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Pages\ListBemImovels;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\BemImovelResource\Widgets\BensImoveisCountStats;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensImoveis\Depreciacao;
use App\Services\Egap\CepService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Livewire;
use Throwable;

class BemImovelResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = BemImovel::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $recordTitleAttribute = 'descricao';

    protected static ?string $modelLabel = 'Bem Imóvel';

    protected static ?string $pluralModelLabel = 'Administração dos Bens Imóveis';

    protected static ?string $navigationLabel = 'Administração';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'bens-imoveis/adm-bens-imoveis';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('TabsBemImovel')
                ->tabs([
                    self::tabImovel(),
                    self::tabLocalizacao(),
                    self::tabDescricao(),
                    self::tabContabil(),
                    self::tabReavaliacao(),
                    self::tabSituacao(),
                ])
                ->columnSpanFull(),
        ]);
    }

    private static function tabImovel(): Tab
    {
        return Tab::make('Imóvel')
            ->icon('heroicon-o-building-office-2')
            ->schema([
                self::section('Identificação', 'heroicon-o-identification', [
                    self::text('num_registro', 'Núm. registro')
                        ->columnSpan(3),
                    self::date('data_construcao', 'Data de construção')
                        ->columnSpan(3),
                    self::text('num_matricula', 'Matrícula')
                        ->columnSpan(3),
                    self::select('Id_setores', 'Setor', 'setoresRelacaoRef', 'Setor')
                        ->columnSpan(6),
                    self::select('id_responsavel', 'Responsável', 'responsavelRelacaoRef', 'descricao')
                        ->columnSpan(6),
                ])
                    ->description('Dados principais de registro e responsabilidade do imóvel.')
                    ->columns(12),

                self::section('Processos', 'heroicon-o-folder-open', [
                    self::text('num_processo_tj', 'Processo TJ'),
                    self::text('num_processo_seger', 'Processo SEGER'),
                    self::select('num_processo_adm', 'Processo Administrativo', 'processoAdmRelacaoRef', 'num_processo'),
                ])
                    ->description('Vinculações processuais relacionadas ao imóvel.')
                    ->columns(3),

                self::section('Valores históricos', 'heroicon-o-banknotes', [
                    MoneyInput::make('valor_historico_escritura')
                        ->label('Valor histórico da escritura')
                        ->columnSpan(4),
                    MoneyInput::make('valor_historico_iptu')
                        ->label('Valor histórico do IPTU')
                        ->columnSpan(4),
                    MoneyInput::make('valor_historico_1a_avaliacao')
                        ->label('Valor histórico da 1ª avaliação')
                        ->columnSpan(4),
                    self::text('criterio_valor_historico', 'Critério do valor histórico')
                        ->columnSpan(6),
                    self::text('criterio_valor_atualizado', 'Critério do valor atualizado')
                        ->columnSpan(6),
                ])
                    ->description('Critérios e valores históricos usados na composição patrimonial.')
                    ->columns(12),
            ]);
    }

    private static function tabLocalizacao(): Tab
    {
        return Tab::make('Localização')
            ->icon('heroicon-o-map-pin')
            ->schema([
                self::section('Endereço', 'heroicon-o-map', [
                    self::text('end_logradouro', 'Logradouro')->columnSpan(7),
                    self::text('end_numero', 'Número')->columnSpan(2),
                    self::text('end_cep', 'CEP')
                        ->mask('99999-999')
                        ->suffixAction(self::buscarCepAction())
                        ->columnSpan(3),
                    self::text('end_estado', 'Estado')->maxLength(2)->columnSpan(4),
                    self::text('end_cidade', 'Cidade')->columnSpan(4),
                    self::text('end_bairro', 'Bairro')->columnSpan(4),
                    self::text('end_compl_endereco', 'Complemento')->columnSpanFull(),
                ])
                    ->columns(12)
                    ->description('Busque o CEP para preenchimento automático dos campos'),

                self::section('Referências geográficas', 'heroicon-o-globe-alt', [
                    self::text('end_latitude', 'Latitude'),
                    self::text('end_longitude', 'Longitude'),
                    self::select('id_cidade', 'Cidade cadastrada', 'cidadeRelacaoRef', 'descricao'),
                    self::select('id_ciduf', 'Cidade/UF', 'cidufRelacaoRef', 'cd_uf'),
                ])
                    ->columns(4),
            ]);
    }

    private static function tabDescricao(): Tab
    {
        return Tab::make('Descrição')
            ->icon('heroicon-o-document-text')
            ->schema([
                self::section('Descrição do imóvel', 'heroicon-o-home-modern', [
                    self::text('descricao', 'Descrição')->columnSpanFull(),
                    self::text('inscricao_generica', 'Inscrição genérica')->columnSpan(4),
                    self::text('inscricao_fiscal', 'Inscrição fiscal')->columnSpan(4),
                    self::text('inscricao_imobiliaria', 'Inscrição imobiliária')->columnSpan(4),
                ])->columns(12),

                self::section('Classificação', 'heroicon-o-squares-2x2', [
                    self::select('Id_tipoimovel', 'Tipo de imóvel', 'tipoImovelRelacaoRef', 'desc_tipo_imovel'),
                    self::select('id_denominacao', 'Denominação', 'denominacaoRelacaoRef', 'denominacao'),
                    self::select('Id_tipodebem', 'Tipo de bem', 'tipoDeBemRelacaoRef', 'Descricao'),
                ])->columns(3),

                self::section('Áreas', 'heroicon-o-arrows-pointing-out', [
                    self::number('area', 'Área')->suffix('m²'),
                    self::number('area_terreno_total', 'Área do terreno total')->suffix('m²'),
                    self::number('area_edificacao', 'Área de edificação')->suffix('m²'),
                ])->columns(3),
            ]);
    }

    private static function tabContabil(): Tab
    {
        return Tab::make('Contábil')
            ->icon('heroicon-o-calculator')
            ->schema([
                self::section('Classificação contábil', 'heroicon-o-clipboard-document-list', [
                    self::select('id_planocontas', 'Conta contábil', 'planoContasRelacaoRef', 'titulo')
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->codigo} / {$record->titulo}")
                        ->searchable(['codigo', 'titulo'])
                        ->columnSpan(6),
                    self::select('id_elementodespesa', 'Elemento de despesa', 'elementoDespesaRelacaoRef', 'DescricaodaClasse')
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->CodigodaClasse} - {$record->DescricaodaClasse}")
                        ->searchable(['CodigodaClasse', 'DescricaodaClasse'])
                        ->columnSpan(6),
                ])->columns(12),

                self::section('Datas e vida útil', 'heroicon-o-calendar-days', [
                    self::date('data_aquisicao', 'Data de aquisição'),
                    self::date('data_incorporacao', 'Data de incorporação'),
                    self::date('data_ingresso_contabil', 'Data de ingresso contábil'),
                    self::number('vida_util', 'Vida útil')->suffix('meses'),
                    self::number('vida_util_remanescente', 'Vida útil remanescente')->suffix('meses'),
                    self::number('idade_aparente_anos', 'Idade aparente')->suffix('anos'),
                ])->columns(3),

                self::section('Valores contábeis', 'heroicon-o-banknotes', [
                    MoneyInput::make('depreciacao_mensal')->label('Depreciação mensal'),
                    MoneyInput::make('depreciacao_acumulada')->label('Depreciação acumulada'),
                    MoneyInput::make('valor_liquido_contabil')->label('Valor líquido contábil'),
                    MoneyInput::make('valor_residual')->label('Valor residual'),
                ])->columns(4),
            ]);
    }

    private static function tabReavaliacao(): Tab
    {
        return Tab::make('Reavaliação')
            ->icon('heroicon-o-currency-dollar')
            ->schema([
                self::section('Última reavaliação', 'heroicon-o-arrow-path', [
                    self::date('data_reavaliacao', 'Data da reavaliação'),
                    MoneyInput::make('valor_atualizado')->label('Valor atualizado'),
                    MoneyInput::make('valor_reavaliado')->label('Valor reavaliado'),
                ])->columns(3),
            ]);
    }

    private static function tabSituacao(): Tab
    {
        return Tab::make('Situação')
            ->icon('heroicon-o-check-circle')
            ->schema([
                self::section('Estado atual', 'heroicon-o-check-badge', [
                    self::select('Id_situacao', 'Situação', 'situacaoRelacaoRef', 'Descricao'),
                    self::select('id_condicaouso', 'Condição de uso', 'condicaoUsoRelacaoRef', 'descricao'),
                    self::select('Id_estadoconservacao', 'Estado de conservação', 'estadoConservacaoRelacaoRef', 'descEstadoConservacao'),
                    self::select('id_entradasaida', 'Entrada/Saída', 'entradaSaidaRelacaoRef', 'tipo')
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->tipo} - {$record->descricao}")
                        ->searchable(['tipo', 'descricao']),
                ])->columns(4),

                self::section('Baixa e observações', 'heroicon-o-archive-box-x-mark', [
                    self::date('data_baixa', 'Data da baixa'),
                    self::text('processo_baixa', 'Processo de baixa'),
                    self::date('data_situacao', 'Data de transferência do ativo'),
                    self::textarea('observacao', 'Observação')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->deferLoading()
            ->defaultSort('Id', 'desc')
            ->columns([
                TableColumns::text('Id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('num_registro', 'Núm. registro'),
                TableColumns::text('descricao', 'Descrição')
                    ->limit(45)
                    ->tooltip(fn($record): ?string => $record->descricao),
                TableColumns::text('setoresRelacaoRef.setor', 'Setor')
                    ->limit(35)
                    ->tooltip(fn($record): ?string => $record->setoresRelacaoRef?->setor),
                TableColumns::text('responsavelRelacaoRef.descricao', 'Responsável')
                    ->limit(30)
                    ->tooltip(fn($record): ?string => $record->responsavelRelacaoRef?->descricao),
                TableColumns::text('end_cidade', 'Cidade'),
                TableColumns::text('cidufRelacaoRef.cd_uf', 'UF'),
                TableColumns::text('tipoImovelRelacaoRef.desc_tipo_imovel', 'Tipo de imóvel'),
                TableColumns::text('denominacaoRelacaoRef.denominacao', 'Denominação'),
                TableColumns::date('data_aquisicao', 'Aquisição'),
                TableColumns::money('valor_reavaliado', 'Valor reavaliado'),
                TableColumns::text('situacaoRelacaoRef.Descricao', 'Situação')->badge(),
                TableColumns::text('condicaoUsoRelacaoRef.descricao', 'Condição'),
                TableColumns::text('estadoConservacaoRelacaoRef.descEstadoConservacao', 'Conservação'),
                TableColumns::text('entradaSaidaRelacaoRef.tipo', 'Entrada/Saída')
                    ->formatStateUsing(fn($record): string => $record->entradaSaidaRelacaoRef
                        ? "{$record->entradaSaidaRelacaoRef->tipo} - {$record->entradaSaidaRelacaoRef->descricao}"
                        : '-'),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::calcularDepreciacaoTableAction(),
                    self::registroDepreciacaoTableAction(),
                    self::tributosTableAction(),
                    self::ocupacaoTableAction(),
                    self::imprimirTermoTableAction(),
                    self::processosTableAction(),
                    self::reavaliacaoTableAction(),
                    self::obrasTableAction(),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }

    // ---- Table Actions -------------------------------------------------------

    private static function calcularDepreciacaoTableAction(): Action
    {
        return Action::make('calcular_depreciacao_action')
            ->label('Calcular Depreciação')
            ->icon('heroicon-o-calculator')
            ->requiresConfirmation()
            ->modalHeading('Calcular depreciação')
            ->modalDescription('Os registros de depreciação existentes a partir da data-base serão recalculados.')
            ->visible(fn(BemImovel $record): bool => (int) $record->id_denominacao === 1)
            ->action(fn(BemImovel $record) => self::executarCalculoDepreciacao($record));
    }

    private static function registroDepreciacaoTableAction(): Action
    {
        return TableModalAction::make('registro_depreciacao_action')
            ->label('Registros de Depreciação')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading(fn(BemImovel $record): string => "Registros de depreciação do imóvel #{$record->Id}")
            ->modalContent(fn(BemImovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.depreciacao-imovel-modal',
                    ['imovelId' => (int) $record->getKey()],
                    "depreciacao-imovel-{$record->getKey()}",
                )
            ));
    }

    private static function tributosTableAction(): Action
    {
        return TableModalAction::make('tributos_action')
            ->label('Tributos')
            ->icon('heroicon-o-clipboard')
            ->modalHeading(fn(BemImovel $record): string => "Tributos do imóvel #{$record->Id}")
            ->modalContent(fn(BemImovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.tributos-modal',
                    ['imovelId' => (int) $record->getKey()],
                    "tributos-{$record->getKey()}",
                )
            ));
    }

    private static function ocupacaoTableAction(): Action
    {
        return TableModalAction::make('ocupacoes_action')
            ->label('Ocupações de Terceiros')
            ->icon('heroicon-o-flag')
            ->modalHeading(fn(BemImovel $record): string => "Ocupações de terceiros do imóvel #{$record->Id}")
            ->modalContent(fn(BemImovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.ocupacoes-modal',
                    ['imovelId' => (int) $record->getKey()],
                    "ocupacoes-{$record->getKey()}",
                )
            ));
    }

    private static function imprimirTermoTableAction(): Action
    {
        return Action::make('imprimir')
            ->label('Imprimir termo')
            ->icon('heroicon-o-printer')
            ->url(fn($record) => "https://sistemas.tjes.jus.br/patrimonio/index.php?option=com_reports&name=termo-imovel&tmpl=component&bens={$record->Id}")
            ->openUrlInNewTab();
    }

    private static function processosTableAction(): Action
    {
        return Action::make('processos_action')
            ->label('Processos')
            ->icon('heroicon-o-folder')
            ->modalHeading(fn(BemImovel $record): string => "Processos do imóvel #{$record->Id}")
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalWidth('7xl')
            ->mountUsing(fn(BemImovel $record) => $record->loadMissing([
                'processoAdmRelacaoRef.tipoProcessoRelacaoRef',
                'processoAdmRelacaoRef.unidadeRequisitanteRelacaoRef',
                'processoAdmRelacaoRef.gestorTitularRelacaoRef',
            ]))
            ->schema(fn(BemImovel $record): array => [
                Section::make('Referências do imóvel')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('num_processo_tj')
                                ->label('Processo TJ')
                                ->placeholder('-'),
                            TextEntry::make('num_processo_seger')
                                ->label('Processo SEGER')
                                ->placeholder('-'),
                            TextEntry::make('processo_administrativo_total')
                                ->label('Processos administrativos vinculados')
                                ->state(fn(BemImovel $record): int => $record->processoAdmRelacaoRef ? 1 : 0)
                                ->badge()
                                ->color('primary'),
                        ]),
                    ]),
                Section::make('Processo administrativo')
                    ->icon('heroicon-o-folder-open')
                    ->schema([
                        TextEntry::make('processo_administrativo_vazio')
                            ->hiddenLabel()
                            ->state('Nenhum processo administrativo relacionado a este bem imóvel.')
                            ->badge()
                            ->color('gray')
                            ->visible(fn(BemImovel $record): bool => $record->processoAdmRelacaoRef === null),
                        Grid::make(12)
                            ->schema([
                                TextEntry::make('processoAdmRelacaoRef.num_processo')
                                    ->label('Nº Processo TJES')
                                    ->placeholder('-')
                                    ->columnSpan(3),
                                TextEntry::make('processoAdmRelacaoRef.no_processo_sei')
                                    ->label('Nº Processo SEI')
                                    ->placeholder('-')
                                    ->columnSpan(3),
                                TextEntry::make('processoAdmRelacaoRef.tipoProcessoRelacaoRef.descricao')
                                    ->label('Tipo')
                                    ->placeholder('-')
                                    ->columnSpan(3),
                                TextEntry::make('processoAdmRelacaoRef.situacao_atual')
                                    ->label('Situação atual')
                                    ->badge()
                                    ->placeholder('-')
                                    ->columnSpan(3),
                                TextEntry::make('processoAdmRelacaoRef.data_abertura')
                                    ->label('Data de abertura')
                                    ->formatStateUsing(fn($state): string => self::formatDate($state))
                                    ->columnSpan(2),
                                TextEntry::make('processoAdmRelacaoRef.data_vigencia')
                                    ->label('Fim da vigência')
                                    ->formatStateUsing(fn($state): string => self::formatDate($state))
                                    ->columnSpan(2),
                                TextEntry::make('processoAdmRelacaoRef.unidadeRequisitanteRelacaoRef.Setor')
                                    ->label('Unidade requisitante')
                                    ->placeholder('-')
                                    ->columnSpan(4),
                                TextEntry::make('processoAdmRelacaoRef.gestorTitularRelacaoRef.name')
                                    ->label('Gestor titular')
                                    ->placeholder('-')
                                    ->columnSpan(4),
                                TextEntry::make('processoAdmRelacaoRef.descricao')
                                    ->label('Descrição')
                                    ->placeholder('-')
                                    ->columnSpan(12),
                            ])
                            ->visible(fn(BemImovel $record): bool => $record->processoAdmRelacaoRef !== null),
                    ]),
            ]);
    }

    private static function reavaliacaoTableAction(): Action
    {
        return TableModalAction::make('reavaliacao_action')
            ->label('Reavaliação dos Imóveis')
            ->icon('heroicon-o-forward')
            ->modalHeading(fn(BemImovel $record): string => "Reavaliações do imóvel #{$record->Id}")
            ->modalContent(fn(BemImovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.reavaliacoes-modal',
                    ['imovelId' => (int) $record->getKey()],
                    "reavaliacoes-{$record->getKey()}",
                )
            ));
    }

    private static function obrasTableAction(): Action
    {
        return TableModalAction::make('obras_action')
            ->label('Obras e Ampliações')
            ->icon('heroicon-o-home')
            ->modalHeading(fn(BemImovel $record): string => "Obras e ampliações do imóvel #{$record->Id}")
            ->modalContent(fn(BemImovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.obras-modal',
                    ['imovelId' => (int) $record->getKey()],
                    "obras-{$record->getKey()}",
                )
            ));
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index' => ListBemImovels::route('/'),
            'create' => CreateBemImovel::route('/create'),
            'edit' => EditBemImovel::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            BensImoveisCountStats::class,
        ];
    }

    // -------------------------------------------------------------------------
    // LÓGICA DE DEPRECIAÇÃO
    // -------------------------------------------------------------------------

    /**
     * Executa o cálculo de depreciação e envia notificação ao usuário.
     * Separa o tratamento de erros da lógica de negócio.
     */
    private static function executarCalculoDepreciacao(BemImovel $record): void
    {
        try {
            $total = self::calcularDepreciacao($record);

            Notification::make()
                ->title('Depreciação calculada')
                ->body("{$total} registro(s) gerado(s).")
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível calcular a depreciação')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Calcula e persiste os registros de depreciação do imóvel.
     *
     * @return int Quantidade de registros gerados.
     *
     * @throws InvalidArgumentException quando os dados do imóvel são insuficientes.
     */
    private static function calcularDepreciacao(BemImovel $record): int
    {
        // Recarrega o registro com o relacionamento necessário para não depender
        // do estado que foi passado (pode estar incompleto/em cache).
        $imovel = BemImovel::query()
            ->with('elementoDespesaRelacaoRef')
            ->findOrFail($record->getKey());

        if ((int) $imovel->id_denominacao !== 1) {
            throw new InvalidArgumentException('A depreciação só pode ser calculada para imóveis com denominação 1.');
        }

        [$dataBase, $valor, $vidaUtil] = self::resolverParametrosDepreciacao($imovel);

        $percentualResidual = self::decimal($imovel->elementoDespesaRelacaoRef?->ValorResidual);
        $valorResidual = $valor * ($percentualResidual / 100);
        $depreciacaoMensal = ($valor - $valorResidual) / $vidaUtil;

        $rows = [];
        $seq = 1;
        $dataCalculo = $dataBase;

        for ($mesesRestantes = $vidaUtil; $mesesRestantes > 0; $mesesRestantes--) {
            $depreciacaoAcumulada = ($seq - 1) * $depreciacaoMensal;

            $rows[] = [
                'date_time' => now(),
                'Id_imovel' => $imovel->getKey(),
                'item' => $seq,
                'data_calculo' => $dataCalculo->toDateString(),
                'valor' => $valor,
                'vida_util' => $mesesRestantes,
                'valor_residual' => $valorResidual,
                'depreciacao_mensal' => $depreciacaoMensal,
                'depreciacao_acumulada' => $depreciacaoAcumulada,
                'valor_liquido_contabil' => $valor - $depreciacaoAcumulada,
            ];

            $seq++;
            $dataCalculo = $dataCalculo->addMonthNoOverflow()->startOfMonth();
        }

        DB::transaction(function () use ($imovel, $dataBase, $rows): void {
            Depreciacao::query()
                ->where('Id_imovel', $imovel->getKey())
                ->whereDate('data_calculo', '>=', $dataBase->toDateString())
                ->delete();

            // Inserção em lotes para evitar queries muito grandes.
            foreach (array_chunk($rows, 500) as $chunk) {
                Depreciacao::query()->insert($chunk);
            }
        });

        return count($rows);
    }

    /**
     * Extrai e valida os três parâmetros essenciais para o cálculo:
     * data-base, valor e vida útil.
     *
     * @return array{CarbonImmutable, float, int}
     *
     * @throws InvalidArgumentException
     */
    private static function resolverParametrosDepreciacao(BemImovel $imovel): array
    {
        $dataReavaliacao = self::dateOrNull($imovel->data_reavaliacao);

        $dataBase = $dataReavaliacao
            ? $dataReavaliacao->subMonthNoOverflow()
            : self::dateOrNull($imovel->data_aquisicao);

        if ($dataBase === null) {
            throw new InvalidArgumentException('Informe a data de aquisição ou a data de reavaliação do imóvel.');
        }

        $valor = $dataReavaliacao
            ? self::decimal($imovel->valor_reavaliado)
            : self::decimal($imovel->valor_historico_1a_avaliacao);

        if ($valor <= 0) {
            throw new InvalidArgumentException('O valor-base para depreciação deve ser maior que zero.');
        }

        $vidaUtil = $dataReavaliacao
            ? (int) $imovel->vida_util
            : (int) ($imovel->elementoDespesaRelacaoRef?->VidaUtil ?? 0);

        if ($vidaUtil <= 0) {
            throw new InvalidArgumentException('Informe a vida útil do imóvel ou do elemento de despesa.');
        }

        return [$dataBase, $valor, $vidaUtil];
    }

    // -------------------------------------------------------------------------
    // HELPERS DE FORMULÁRIO
    // -------------------------------------------------------------------------

    private static function buscarCepAction(): Action
    {
        return Action::make('buscar_cep')
            ->icon('heroicon-o-magnifying-glass')
            ->tooltip('Buscar CEP')
            ->action(function (Set $set, string $state, CepService $cepService): void {
                $endereco = $cepService->buscar($state);

                if ($endereco === null) {
                    Notification::make()
                        ->title('CEP não encontrado')
                        ->body('Verifique o CEP informado e tente novamente.')
                        ->warning()
                        ->send();

                    return;
                }

                $set('end_logradouro', $endereco['logradouro']);
                $set('end_bairro', $endereco['bairro']);
                $set('end_cidade', $endereco['cidade']);
                $set('end_estado', $endereco['uf']);
                $set('end_compl_endereco', $endereco['complemento']);

                if ($endereco['latitude'] !== '') {
                    $set('end_latitude', $endereco['latitude']);
                }

                if ($endereco['longitude'] !== '') {
                    $set('end_longitude', $endereco['longitude']);
                }

                Notification::make()
                    ->title('Endereço preenchido a partir do CEP')
                    ->success()
                    ->send();
            });
    }

    private static function section(string $heading, string $icon, array $schema): Section
    {
        return Section::make($heading)
            ->icon($icon)
            ->schema($schema);
    }

    private static function select(string $field, string $label, string $relationship, string $titleAttribute): Select
    {
        return Select::make($field)
            ->label($label)
            ->relationship($relationship, $titleAttribute)
            ->searchable()
            ->preload()
            ->native(false)
            ->optionsLimit(50)
            ->placeholder("Selecione {$label}");
    }

    private static function text(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label);
    }

    private static function number(string $field, string $label): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->numeric()
            ->placeholder('0');
    }

    private static function textarea(string $field, string $label): Textarea
    {
        return Textarea::make($field)
            ->label($label)
            ->rows(3)
            ->placeholder($label);
    }

    private static function date(string $field, string $label): DatePicker
    {
        return DatePicker::make($field)
            ->label($label)
            ->displayFormat('d/m/Y')
            ->native(false)
            ->placeholder('dd/mm/aaaa');
    }

    private static function dateTime(string $field, string $label): DateTimePicker
    {
        return DateTimePicker::make($field)
            ->label($label)
            ->default(now())
            ->displayFormat('d/m/Y H:i:s')
            ->native(false);
    }

    // -------------------------------------------------------------------------
    // HELPERS DE FORMATAÇÃO
    // -------------------------------------------------------------------------

    private static function dateOrNull(mixed $value): ?CarbonImmutable
    {
        if (! $value || str_starts_with((string) $value, '0000-00-00')) {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }

    private static function decimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private static function formatMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    private static function formatDate(mixed $value): string
    {
        if (! $value || str_starts_with((string) $value, '0000-00-00')) {
            return '-';
        }

        return CarbonImmutable::parse($value)->format('d/m/Y');
    }
}
