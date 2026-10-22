<?php

namespace App\Filament\Egap\Interno\Groups\Agendamento\Resources;

use App\Filament\Egap\Interno\Groups\Agendamento\Resources\SolicitacaoResource\Pages\ListSolicitacaos;
use App\Filament\Egap\Interno\Permissions\Agendamento\AgendamentoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Agendamento\Equipe;
use App\Models\egap\Agendamento\Frota;
use App\Models\egap\Agendamento\Recursos;
use App\Models\egap\Agendamento\Regiao;
use App\Models\egap\Agendamento\Solicitacao;
use App\Models\egap\Cadastro\Setores;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SolicitacaoResource extends AgendamentoResource
{
    protected static ?string $model = Solicitacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Agendamento';
    protected static ?string $slug = 'agendamento/agendamento-de-veiculos';
    protected static ?string $modelLabel = 'Solicitação';
    protected static ?string $pluralModelLabel = 'Solicitações';
    protected static ?string $navigationLabel = 'Agendamento de Veículos';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Solicitação')
                    ->tabs([
                        Tab::make('Dados principais')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('id_solicitante')
                                            ->label('Solicitante')
                                            ->relationship('idSolicitanteRef', 'name')
                                            ->searchable()
                                            ->default(fn() => session())
                                            ->preload()
                                            ->placeholder('Selecione o solicitante')
                                            ->native(false)
                                            ->required()
                                            ->columnSpanFull(),

                                        Select::make('id_situacao')
                                            ->label('Situação')
                                            ->relationship('idSituacaoRef', 'Descricao')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Selecione a situação')
                                            ->native(false)
                                            ->required()
                                            ->columnSpan(4),

                                        Select::make('tipo')
                                            ->label('Tipo')
                                            ->options([
                                                '1' => 'Agendamento de Veículos',
                                                '2' => 'Transporte de Carga',
                                            ])
                                            ->required()
                                            ->native(false)
                                            ->placeholder('Informe o tipo da solicitação')
                                            ->columnSpan(4),

                                        Select::make('regiao')
                                            ->label('Região')
                                            ->options(
                                                fn () => Regiao::query()
                                                    ->pluck('sigla', 'id')
                                                    ->toArray()
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Selecione a região')
                                            ->native(false)
                                            ->columnSpan(4),

                                        Select::make('unidade_solicitante')
                                            ->label('Unidade Judiciária')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->options(
                                                fn () => Setores::query()
                                                    ->whereColumn('id', 'codigopai')
                                                    ->orderBy('unidadeorganizacional')
                                                    ->pluck('unidadeorganizacional', 'codigopai')
                                                    ->toArray()
                                            )
                                            ->afterStateUpdated(
                                                fn (Set $set) => $set('setor_solicitante', null)
                                            )
                                            ->columnSpan(6),

                                        Select::make('setor_solicitante')
                                            ->label('Setor solicitante')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->options(
                                                fn (Get $get) => blank($get('unidade_solicitante'))
                                                    ? []
                                                    : Setores::query()
                                                        ->where('codigodauo', $get('unidade_solicitante'))
                                                        ->whereColumn('id', '<>', 'codigopai')
                                                        ->orderBy('setor')
                                                        ->pluck('setor', 'id')
                                                        ->toArray()
//                                                    : Setores::query()
//                                                        ->whereIn('id', Setores::descendentesIds((int) $get('unidade_solicitante')))
//                                                        ->orderBy('setor')
//                                                        ->pluck('setor', 'id')
//                                                        ->toArray()
                                            )
                                            ->disabled(fn (Get $get) => blank($get('unidade_solicitante')))
                                            ->columnSpan(6),
                                    ])
                            ]),

                        Tab::make('Período')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        DatePicker::make('data_inicio')
                                            ->label('Data início')
                                            ->columnSpan(8)
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->required(),

                                        TimePicker::make('hora_inicio')
                                            ->label('Hora início')
                                            ->columnSpan(4)
                                            ->seconds(false)
                                            ->required(),

                                        DatePicker::make('data_termino')
                                            ->label('Data término')
                                            ->columnSpan(8)
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->required(),

                                        TimePicker::make('hora_termino')
                                            ->label('Hora término')
                                            ->columnSpan(4)
                                            ->seconds(false)
                                            ->required(),
                                    ])
                            ]),

                        Tab::make('Locais e observações')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Deslocamento')
                                    ->description('Informações de origem e destino.')
                                    ->schema([
                                        TextInput::make('local_saida')
                                            ->label('Local de saída')
                                            ->placeholder('Informe o local de saída')
                                            ->maxLength(255)
                                            ->required(),

                                        TextInput::make('local_destino')
                                            ->label('Local de destino')
                                            ->placeholder('Informe o local de destino')
                                            ->maxLength(255)
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Section::make('Observações e justificativas')
                                    ->description('Detalhamentos complementares da solicitação.')
                                    ->schema([
                                        Textarea::make('justificativa')
                                            ->label('Justificativa')
                                            ->rows(5)
                                            ->autosize()
                                            ->columnSpanFull()
                                            ->placeholder('Descreva a justificativa da solicitação')
                                            ->formatStateUsing(fn($state): ?string => is_array($state) ? ($state['justificativa'] ?? null) : $state)
                                            ->dehydrateStateUsing(fn(?string $state, ?Solicitacao $record): array => [
                                                ...(is_array($record?->justificativa) ? $record->justificativa : []),
                                                'justificativa' => $state,
                                            ])
                                            ->required(),

                                        Textarea::make('motivo_cancelamento')
                                            ->label('Motivo do cancelamento')
                                            ->rows(4)
                                            ->autosize()
                                            ->columnSpanFull()
                                            ->placeholder('Informe o motivo do cancelamento, se houver'),

                                        Textarea::make('motivo_edicao')
                                            ->label('Motivo da edição')
                                            ->rows(4)
                                            ->autosize()
                                            ->columnSpanFull()
                                            ->placeholder('Informe o motivo da edição, se houver'),

                                        Textarea::make('finalizar')
                                            ->label('Finalização')
                                            ->rows(4)
                                            ->autosize()
                                            ->columnSpanFull()
                                            ->placeholder('Informações de finalização'),

                                        FileUpload::make('anexo')
                                            ->label('Anexo PDF')
                                            ->columnSpanFull()
                                            ->disk('egap')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->directory('files/agendamento')
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),

                TableColumns::text('idSituacaoRef.Descricao', 'Situação')
                    ->badge()
                    ->color(fn(?string $state) => match (mb_strtolower($state ?? '')) {
                        'pendente' => 'warning',
                        'aprovado', 'deferido', 'ativo' => 'success',
                        'cancelado', 'indeferido' => 'danger',
                        default => 'gray',
                    }),

                TableColumns::text('idSolicitanteRef.name', 'Solicitante'),

                TableColumns::text('unidadeSolicitanteRef.unidadeorganizacional', 'Unidade')
                    ->description(fn($record): string => $record->setorSolicitanteRef->setor ?? '-'),

                TableColumns::text('regiaoRef.sigla', 'Região')
                    ->badge()
                    ->color('info'),

                TableColumns::date('data_inicio', 'Data início'),

                TableColumns::text('local_saida', 'Local de saída'),

                TableColumns::text('justificativa', 'Detalhamento')
                    ->state(fn($record): ?string => data_get($record->justificativa, 'justificativa')),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                self::agendarTableAction()
            ])
            ->filters([
                SelectFilter::make('situacao_filter')
                    ->label('Situação')
                    ->columnSpan(2)
                    ->relationship('idSituacaoRef', 'Descricao')
                    ->default(6)
            ])
            ->defaultSort('date_time', 'desc');
    }

    private static function agendarTableAction(): Action
    {
        return Action::make('agendar_veiculo')
            ->hiddenLabel()
            ->tooltip('Agendar')
            ->icon('heroicon-o-calendar-days')
            ->modalHeading(fn($record) => 'Agendar veículo #' . $record->id)
            ->modalSubmitActionLabel('Agendar')
            ->schema([
                Select::make('condutor_veiculo')
                    ->required()
                    ->native(false)
                    ->label('Condutor')
                    ->options(Equipe::all()->pluck('idPessoaRef.name', 'id')),
                Select::make('veiculo')
                    ->label('Veículo')
                    ->native(false)
                    ->required()
                    ->options(Frota::all()->pluck('descricao', 'id')),
                Textarea::make('observacao')
                    ->required()
                    ->label('Observação')
                    ->rows(4)
            ])
            ->action(function (array $data, $record): void {
                if ($data) {
                    Recursos::create([
                        'condutor' => $data['condutor_veiculo'],
                        'veiculo' => $data['veiculo'],
                        'id_solicitacao' => $record->id,
                        'observacao' => $data['observacao'],
                    ]);

                    $record->update(['id_situacao' => 8]);

                    Notification::make()
                        ->color('success')
                        ->icon(Heroicon::CalendarDays)
                        ->title('Agendamento concluído!')
                        ->send();
                } else {
                    Notification::make()
                        ->color('danger')
                        ->icon(Heroicon::ExclamationCircle)
                        ->title('Erro ao concluir agendamento')
                        ->send();
                }
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitacaos::route('/'),
        ];
    }
}
