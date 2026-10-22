<?php

namespace App\Filament\Egap\Interno\Groups\Processo\Resources;

use App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource\Pages\CreateProcessosAdm;
use App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource\Pages\EditProcessosAdm;
use App\Filament\Egap\Interno\Groups\Processo\Resources\ProcessosAdmResource\Pages\ListProcessosAdms;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensImoveis\Processo;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Processo\ProcessoResource;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class ProcessosAdmResource extends ProcessoResource
{
    protected static ?string $model = Processo::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder-open';
    protected static string | \UnitEnum | null $navigationGroup = 'Processos';
    protected static ?string $navigationLabel = 'Processos Administrativos';
    protected static ?string $modelLabel = 'Processo Administrativo';
    protected static ?string $pluralModelLabel = 'Processos Administrativos';
    protected static ?string $slug = 'processos/processos-adm';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Processos Administrativos')
                            ->schema([
                                TextInput::make('num_processo')
                                    ->label('Nº Processo TJES')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->mask('9999.99.999.999')
                                    ->placeholder('0000.00.000.000'),

                                TextInput::make('no_processo_sei')
                                    ->label('No Processo SEI')
                                    ->unique(ignoreRecord: true)
                                    ->mask('9999999-99.9999.9.99.9999')
                                    ->placeholder('0000000-00.0000.0.00.0000'),

                                Select::make('id_tipo_processo')
                                    ->label('Tipo de Processo')
                                    ->relationship('tipoProcessoRelacaoRef', 'descricao')
                                    ->searchable()
                                    ->preload(),

                                DatePicker::make('data_abertura')
                                    ->label('Data de Abertura')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                Select::make('unidade_demandante')
                                    ->label('Unidade Requisitante')
                                    ->relationship('unidadeRequisitanteRelacaoRef', 'Setor')
                                    ->searchable(),

                                DatePicker::make('data_vigencia')
                                    ->label('Data de encerramento da vigência')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                Textarea::make('descricao')
                                    ->label('Descrição')
                                    ->columnSpan(1)
                                    ->rows(4),

                                Select::make('id_processo_pai')
                                    ->label('Relacionado ao Processo')
                                    ->relationship('processoPaiRelacaoRef', 'num_processo')
                                    ->searchable()
                                    ->columnSpan(1),

                                Select::make('id_fornecedor')
                                    ->label('Fornecedor')
                                    ->relationship('fornecedorRelacaoRef', 'NomeFornecedor')
                                    ->searchable(),

                                Select::make('situacao_atual')
                                    ->label('Situação Atual')
                                    ->options([
                                        'Aguardando validação' => 'Aguardando validação',
                                        'Enviado para Empenho' => 'Enviado para Empenho',
                                        'Empenhado' => 'Empenhado',
                                        'Ordem de Entrega Emitida' => 'Ordem de Entrega Emitida',
                                        'Material Recebido' => 'Material Recebido',
                                    ])
                                    ->searchable(),

                                Select::make('projeto_atividade')
                                    ->label('Projeto/Atividade')
                                    ->relationship('projetoAtividadeRef', 'descricao')
                                    ->searchable()
                                    ->columnSpan(1),
                            ])->columns(2),

                        Tab::make('Gestores')
                            ->schema([
                                Select::make('gestor_titular')
                                    ->label('Gestor Titular')
                                    ->relationship('gestorTitularRelacaoRef', 'name')
                                    ->searchable()
                                    ->columnSpanFull(),

                                Select::make('gestor_substituto')
                                    ->label('Gestor Substituto')
                                    ->relationship('gestorSubstitutoRelacaoRef', 'name')
                                    ->searchable()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('num_processo', 'Nº Processo TJES')
                    ->badge()
                    ->color('primary'),

                TableColumns::text('no_processo_sei', 'Nº Processo SEI')
                    ->badge()
                    ->color('primary'),

                TableColumns::text('tipoProcessoRelacaoRef.descricao', 'Tipo de Processo'),

                TableColumns::date('data_abertura', 'Data de Abertura'),
                    //->extraCellAttributes(['style' => 'padding-right: 16px;']),

                TableColumns::text('unidadeRequisitanteRelacaoRef.setor', 'Unidade Requisitante')
                    ->badge()
                    ->color('gray')
                    ->wrap(),

                TableColumns::date('data_vigencia', 'Data de encerramento da vigência'),
                    //->extraCellAttributes(['style' => 'padding-right:24px;']),

                TableColumns::text('descricao', 'Descrição')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->descricao),

                TableColumns::text('situacao_atual', 'Situação Atual'),

                TableColumns::text('gestorTitularRelacaoRef.name', 'Gestor Titular'),

            ])
            ->defaultSort('date_time', 'DESC')
            ->filters([])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::documentosTableAction(),
                    self::materiaisTableAction(),
                    self::reservasTableAction(),
                    self::empenhosTableAction(),
//                    self::solicitacaoReservaTableAction(),
//                    self::solicitacaoEmpenhoTableAction(),
//                    self::termoReferenciaTableAction(),
                ])
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-vertical'),
            ]);
    }

    private static function documentosTableAction()
    {
        return TableModalAction::make('documentos_action')
            ->label('Documentos')
            ->icon(Heroicon::OutlinedDocument)
            ->modalHeading(fn($record): string => "Anexos do Processo #{$record->num_processo}")
            ->modalContent(fn($record): HtmlString => new HtmlString(
                Livewire::mount('processos.documentos-modal',
                ['processoId' => (int) $record->id],
                    "documentos-processo-{$record->getKey()}",
                )
            ));
    }
    private static function materiaisTableAction()
    {
        return TableModalAction::make('materiais_action')
            ->label('Materiais')
            ->icon(Heroicon::OutlinedSquares2x2)
            ->modalHeading(fn($record): string => "Materiais do Processo #{$record->num_processo}")
            ->modalContent(fn($record): HtmlString => new HtmlString(
                Livewire::mount('processos.materiais-modal',
                    ['processoId' => (int) $record->id],
                    "materiais-processo-{$record->getKey()}",
                )
            ));
    }
    private static function reservasTableAction()
    {
        return TableModalAction::make('reservas_action')
            ->label('Reservas')
            ->icon(Heroicon::OutlinedFolder)
            ->modalHeading(fn($record): string => "Reservas do Processo #{$record->num_processo}")
            ->modalContent(fn($record): HtmlString => new HtmlString(
                Livewire::mount('processos.reservas-modal',
                    ['processoId' => (int) $record->id],
                    "reservas-processo-{$record->getKey()}",
                )
            ));
    }
    private static function empenhosTableAction()
    {
        return TableModalAction::make('empenhos_action')
            ->label('Empenhos')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->modalHeading(fn($record): string => "Empenhos do Processo #{$record->num_processo}")
            ->modalContent(fn($record): HtmlString => new HtmlString(
                Livewire::mount('processos.empenhos-modal',
                    ['processoId' => (int) $record->id],
                    "empenhos-processo-{$record->getKey()}",
                )
            ));
    }
    private static function solicitacaoReservaTableAction()
    {
        return TableModalAction::make('solicitacao_reserva_action');
    }
    private static function solicitacaoEmpenhoTableAction()
    {
        return TableModalAction::make('solicitacao_empenho_action');
    }
    private static function termoReferenciaTableAction()
    {
        return TableModalAction::make('termo_referencia_action');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProcessosAdms::route('/'),
            'create' => CreateProcessosAdm::route('/create'),
            'edit' => EditProcessosAdm::route('/{record}/edit'),
        ];
    }
}
