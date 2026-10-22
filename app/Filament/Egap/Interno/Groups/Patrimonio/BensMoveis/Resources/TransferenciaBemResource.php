<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource\Pages\CreateTransferenciaBem;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource\Pages\EditTransferenciaBem;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\TransferenciaBemResource\Pages\ListTransferenciaBems;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Agendamento\Materiais;
use App\Models\egap\Agendamento\Regiao;
use App\Models\egap\Agendamento\Solicitacao;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use RuntimeException;
use Throwable;

class TransferenciaBemResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = TransferenciaBemMovel::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Histórico das movimentações';

    protected static ?string $modelLabel = 'Transferência';

    protected static ?string $pluralModelLabel = 'Histórico das Movimentações';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'bens-moveis/movimentacoes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação da movimentação')
                    ->description('Selecione o bem e, quando houver, vincule o termo e o pedido.')
                    ->icon('heroicon-o-arrows-right-left')
                    ->columns(3)
                    ->schema([
                        Select::make('NumPatrimonio')
                            ->label('Nº Patrimônio')
                            ->relationship('bem', 'NumPatrimonio')
                            ->getOptionLabelFromRecordUsing(fn(BemMovel $record): string => filled($record->Descricao)
                                ? "{$record->NumPatrimonio} - {$record->Descricao}"
                                : (string) $record->NumPatrimonio)
                            ->searchable(['NumPatrimonio', 'Descricao'])
                            ->optionsLimit(50)
                            ->native(false)
                            ->placeholder('Selecione o patrimônio')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $bem = filled($state)
                                    ? BemMovel::query()->where('NumPatrimonio', $state)->first()
                                    : null;

                                $set('UnidadeAnterior', $bem?->UnidadeJudiciaria);
                                $set('SetorAnterior', $bem?->Setor);
                                $set('ComplementoAnterior', $bem?->ComplementoSetor);
                                $set('AndarAnterior', $bem?->AndarSetor);
                            }),

                        Select::make('Termo')
                            ->label('Termo')
                            ->relationship('termoRel', 'num_termo')
                            ->getOptionLabelFromRecordUsing(fn($record): string => $record->termo_completo)
                            ->searchable(['num_termo', 'ano_termo'])
                            ->optionsLimit(50)
                            ->native(false)
                            ->placeholder('Selecione o termo'),

                        Select::make('pedido_no')
                            ->label('Pedido Nº')
                            ->relationship('pedido', 'id')
                            ->getOptionLabelFromRecordUsing(fn(Pedidos $record): string => filled($record->num_protocolo)
                                ? "{$record->id} - Protocolo {$record->num_protocolo}"
                                : (string) $record->id)
                            ->searchable(['id', 'num_protocolo'])
                            ->optionsLimit(50)
                            ->native(false)
                            ->placeholder('Selecione o pedido'),
                    ]),

                Section::make('Localização anterior')
                    ->description('A origem é preenchida conforme a localização atual do patrimônio selecionado e pode ser corrigida quando necessário.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([
                        Select::make('UnidadeAnterior')
                            ->label('Unidade Judiciária Anterior')
                            ->options(fn(): array => self::unidadesJudiciariasOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live()
                            ->placeholder('Selecione a unidade anterior')
                            ->afterStateUpdated(function (Set $set): void {
                                $set('SetorAnterior', null);
                            }),

                        Select::make('SetorAnterior')
                            ->label('Setor Anterior')
                            ->options(fn(Get $get): array => self::setoresOptions($get('UnidadeAnterior')))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->disabled(fn(Get $get): bool => blank($get('UnidadeAnterior')))
                            ->placeholder('Selecione o setor anterior'),

                        Select::make('ComplementoAnterior')
                            ->label('Complemento Anterior')
                            ->relationship('complementoAnteriorRel', 'descricao')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o complemento anterior'),

                        TextInput::make('AndarAnterior')
                            ->label('Andar Anterior')
                            ->maxLength(255)
                            ->placeholder('Informe o andar anterior'),
                    ]),

                Section::make('Localização atual')
                    ->description('Informe a unidade e o setor de destino do patrimônio.')
                    ->icon('heroicon-o-building-office-2')
                    ->columns(2)
                    ->schema([
                        Select::make('UnidadeAtual')
                            ->label('Unidade Judiciária Atual')
                            ->options(fn(): array => self::unidadesJudiciariasOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->live()
                            ->placeholder('Selecione a unidade atual')
                            ->afterStateUpdated(function (Set $set): void {
                                $set('SetorAtual', null);
                            }),

                        Select::make('SetorAtual')
                            ->label('Setor Atual')
                            ->options(fn(Get $get): array => self::setoresOptions($get('UnidadeAtual')))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->disabled(fn(Get $get): bool => blank($get('UnidadeAtual')))
                            ->placeholder('Selecione o setor atual'),

                        Select::make('ComplementoAtual')
                            ->label('Complemento Atual')
                            ->relationship('complementoAtualRel', 'descricao')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Selecione o complemento atual'),

                        TextInput::make('AndarAtual')
                            ->label('Andar Atual')
                            ->maxLength(255)
                            ->placeholder('Informe o andar atual'),
                    ]),
            ]);
    }

    private static function unidadesJudiciariasOptions(): array
    {
        return Setores::query()
            ->whereColumn('id', 'codigopai')
            ->orderBy('setor')
            ->pluck('setor', 'id')
            ->toArray();
    }

    private static function setoresOptions(mixed $unidadeId): array
    {
        if (blank($unidadeId)) {
            return [];
        }

        return Setores::query()
            ->where('codigopai', $unidadeId)
            ->orderBy('setor')
            ->pluck('setor', 'id')
            ->toArray();
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->deferLoading()
            ->columns([
                TableColumns::text('NumPatrimonio', 'Nº Patrimônio', isFirstColumn: true)
                    ->badge(),
                TableColumns::text('unidadeAnteriorRel.setor', 'Unidade Judiciária Anterior')
                    ->description(fn(TransferenciaBemMovel $record): ?string => $record->setorAnteriorRel?->setor)
                    ->wrap(),
                TableColumns::text('complementoAnteriorRel.descricao', 'Complemento Anterior')
                    ->wrap(),
                TableColumns::text('unidadeAtualRel.setor', 'Unidade Judiciária Atual')
                    ->description(fn(TransferenciaBemMovel $record): ?string => $record->setorAtualRel?->setor)
                    ->wrap(),
                TableColumns::text('complementoAtualRel.descricao', 'Complemento Atual')
                    ->wrap(),
                TableColumns::updatedBy('usuarioRef.name'),
                TableColumns::text('TermoVinculado')
                    ->label('Termo Vinculado')
                    ->formatStateUsing(fn(TransferenciaBemMovel $record): string => $record->termoRel
                        ? "{$record->termoRel->num_termo}/{$record->termoRel->ano_termo}"
                        : '-')
                    ->color('primary')
                    ->weight('bold')
                    ->extraAttributes([
                        'class' => 'cursor-pointer underline',
                    ])
                    ->tooltip('Visualizar Termo')
                    ->action(self::visualizarTermoModal()),
                TableColumns::text('pedido_no', 'Pedido Nº')
                    ->formatStateUsing(fn(TransferenciaBemMovel $record): string => $record->pedido_no ? 0 : '-'),
            ])
            ->defaultSort('date_time', 'desc')
            ->recordActions([
                ...TableDefaults::actions(),
                self::encaminharLogisiticaTableAction(),
            ]);
    }

    public static function visualizarTermoModal(): Action
    {
        return TableModalAction::make('visualizar_termo')
            ->visible(fn(TransferenciaBemMovel $record): bool => filled($record->Termo))
            ->modalHeading(fn(TransferenciaBemMovel $record): string => $record->termoRel
                ? "Validar Termo - {$record->termoRel->num_termo}/{$record->termoRel->ano_termo}"
                : 'Validar Termo')
            ->modalContent(fn(TransferenciaBemMovel $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.validar-termo-modal',
                    ['termoId' => (int) $record->Termo],
                    "validar-termo-{$record->getKey()}",
                )
            ));
    }

    public static function encaminharLogisiticaTableAction(): Action
    {
        return Action::make('encaminhar_logistica')
            ->tooltip('Encaminhar para logística')
            ->hiddenLabel()
            ->icon(Heroicon::ArrowRightStartOnRectangle)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Encaminhar para logística')
            ->modalDescription('Deseja gerar a solicitação de transporte para a Seção de Patrimônio recolher/enviar este bem?')
            ->action(function (TransferenciaBemMovel $record): void {
                $userId = auth()->id();

                try {
                    DB::connection('egap')->transaction(function () use ($record, $userId): void {
                        $record->loadMissing('termoRel');

                        $termo = $record->termoRel;

                        if (! $termo) {
                            throw new RuntimeException('Dados do termo não encontrados para originar o transporte.');
                        }

                        $regiaoId = Regiao::query()
                            ->where('unidade', $record->UnidadeAtual)
                            ->value('id');

                        $solicitacaoId = Solicitacao::query()->insertGetId([
                            'date_time' => now(),
                            'id_user' => $userId,
                            'tipo' => 2,
                            'id_situacao' => 6,
                            'id_solicitante' => $termo->atualizado_por,
                            'unidade_solicitante' => $record->UnidadeAtual,
                            'setor_solicitante' => $record->SetorAtual,
                            'regiao' => $regiaoId,
                            'justificativa' => 'Solicitação de transporte gerada via e-GAP Laravel.',
                            'local_saida' => 'Seção de Patrimônio',
                        ]);

                        Materiais::query()->insert([
                            'date_time' => now(),
                            'id_pedido' => $record->pedido_no,
                            'id_termo' => $record->Termo,
                            'id_user' => $userId,
                            'id_solicitacao' => $solicitacaoId,
                        ]);
                    });

                    Notification::make()
                        ->title('Solicitação encaminhada corretamente!')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Erro ao encaminhar')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public static function atualizarDadosTableAction(): Action
    {
        return Action::make('atualizar_dados')
            ->label('Atualizar dados')
            ->icon('heroicon-m-chevron-double-up')
            ->color('gray')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('elemento')
                        ->label('Elemento')
                        ->options([
                            'UnidadeAtual' => 'Unidade Judiciária Atual',
                            'SetorAtual' => 'Setor Atual',
                        ])
                        ->required()
                        ->live(),
                    Select::make('valor')
                        ->label('Valor')
                        ->options(function (Get $get) {
                            return match ($get('elemento')) {
                                'UnidadeAtual' => Setores::query()
                                    ->whereColumn('id', 'CodigoPai')
                                    ->orderBy('setor')
                                    ->pluck('setor', 'id'),
                                'SetorAtual' => Setores::query()
                                    ->whereColumn('id', '!=', 'CodigoPai')
                                    ->orderBy('setor')
                                    ->pluck('setor', 'id'),
                                default => [],
                            };
                        })
                        ->searchable()
                        ->required(),
                ]),
            ])
            ->action(function (TransferenciaBemMovel $record, array $data): void {
                $record->update([
                    $data['elemento'] => $data['valor'],
                ]);

                Notification::make()
                    ->title('Dados da movimentação atualizados com sucesso!')
                    ->success()
                    ->send();
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'complementoAnteriorRel',
            'complementoAtualRel',
            'setorAnteriorRel',
            'setorAtualRel',
            'termoRel',
            'unidadeAnteriorRel',
            'unidadeAtualRel',
            'usuarioRef',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferenciaBems::route('/'),
            'create' => CreateTransferenciaBem::route('/create'),
            'edit' => EditTransferenciaBem::route('/{record}/edit'),
        ];
    }
}
