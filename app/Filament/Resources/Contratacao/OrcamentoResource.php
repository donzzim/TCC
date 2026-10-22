<?php

namespace App\Filament\Resources\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use App\Filament\Clusters\Contratacao\OrcamentoCluster;
use App\Filament\Resources\Cadastro\NaturezaDespesaResource;
use App\Filament\Resources\Contratacao\OrcamentoResource\Pages;
use App\Filament\Resources\OrcamentoResource\RelationManagers;
use App\Models\Contratacao\Orcamento;
use App\Models\Cadastro\Tabelas;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists;
use Filament\Actions\ReplicateAction;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;

class OrcamentoResource extends Resource
{
    protected static ?string $model = Orcamento::class;
    protected static ?string $cluster = OrcamentoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Orçamento';
    protected static ?string $puralModelLabel = 'Orçamento ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Orçamentos';
    // protected static string | UnitEnum | null $navigationGroup = 'Orçamento';
    protected static ?int $navigationSort = 1;

    public static function getFormfields(): array
    {
        return [
            Grid::make()
                ->schema([
                    Forms\Components\Hidden::make('setor_id'),
                    Forms\Components\Select::make('natureza_id')
                        // ->relationship(
                        //     name: 'natureza',
                        //     titleAttribute: 'descricao'
                        // )
                        ->options(function () {
                            $rows = Tabelas::where('grupo', '=', 'NATUREZA_DESPESA')->get();
                            $result = [];
                            foreach ($rows as $row) {
                                $result[$row->id] = "{$row->descricao} - {$row->codigo}";
                            }
                            return $result;
                        })
                        ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->codigo}: {$record->descricao}")
                        ->searchable(['codigo', 'descricao'])
                        ->label('Natureza de Despesa')
                        ->preload()
                        ->required()
                        ->createOptionForm(
                            fn(Schema $form) => NaturezaDespesaResource::form($form)
                        )
                        ->columnSpan(3),

                    Forms\Components\Select::make('planoorcamentario_id')
                        // ->relationship(
                        //     name: 'plano',
                        //     titleAttribute: 'descricao'
                        // )
                        ->options(function () {
                            $rows = Tabelas::where('grupo', '=', 'PLANO_ORCAMENTARIO')->get();
                            $result = [];
                            foreach ($rows as $row) {
                                $result[$row->id] = "{$row->descricao} - {$row->codigo}";
                            }
                            return $result;
                        })
                        ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->codigo}: {$record->descricao}")
                        ->searchable(['descricao'])
                        ->label('Plano Orçamentário')
                        ->preload()
                        ->required()
                        ->createOptionForm(
                            fn(Schema $form) => PlanoOrcamentarioResource::form($form)
                        )
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('ano')
                        ->label('Ano')
                        ->required()
                        ->numeric()
                        ->columnSpan(1),
                ])
                ->columns(6)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('descricao')
                ->label('Descrição')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Grid::make()
                ->schema([
                    Section::make('Previsão Orçamentária')
                        ->description('Dados solicitados')
                        ->schema([

                            Forms\Components\TextInput::make('previsao_1instancia')
                                ->label('Previsão 1 Instância')
                                ->inlineLabel()
                                ->required()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $valor1 = (float) $get('previsao_1instancia');
                                    $valor2 = (float) $get('previsao_2instancia');
                                    $total = $valor1 + $valor2;

                                    $set('previsao_total', $total);
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('previsao_2instancia')
                                ->label('Previsão 2 Instância')
                                ->inlineLabel()
                                ->required()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $valor1 = (float) $get('previsao_1instancia');
                                    $valor2 = (float) $get('previsao_2instancia');
                                    $total = $valor1 + $valor2;

                                    $set('previsao_total', $total);
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('previsao_total')
                                ->label('Previsão Total')
                                ->inlineLabel()
                                ->required()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->columnSpan(1),
                        ])
                        ->columnSpan(2),

                    Section::make('Orçamento Aprovado')
                        ->description('Dados do orçamento aprovado')
                        ->schema([
                            Forms\Components\TextInput::make('valor_1instancia')
                                ->label('Aprovado 1 Instância')
                                ->required()
                                ->inlineLabel()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $valor1 = (float) $get('valor_1instancia');
                                    $valor2 = (float) $get('valor_2instancia');
                                    $total = $valor1 + $valor2;

                                    $set('valor_total', $total);
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('valor_2instancia')
                                ->label('Aprovado 2 Instância')
                                ->required()
                                ->inlineLabel()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $valor1 = (float) $get('valor_1instancia');
                                    $valor2 = (float) $get('valor_2instancia');
                                    $total = $valor1 + $valor2;

                                    $set('valor_total', $total);
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('valor_total')
                                ->label('Aprovado Total')
                                ->required()
                                ->inlineLabel()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->columnSpan(1),
                        ])
                        ->columnSpan(2),

                    Section::make('Saldo Orçamentário')
                        ->description('Dados do saldo orçamentário atual ')
                        ->schema([
                            Forms\Components\TextInput::make('saldo_1instancia')
                                ->label('Saldo 1 Instância')
                                ->inlineLabel()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $valor1 = (float) $get('saldo_1instancia');
                                    $valor2 = (float) $get('saldo_2instancia');
                                    $total = $valor1 + $valor2;

                                    $set('saldo_total', $total);
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('saldo_2instancia')
                                ->label('Saldo 2 Instância')
                                ->inlineLabel()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->live(debounce: 800)
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $valor1 = (float) $get('saldo_1instancia');
                                    $valor2 = (float) $get('saldo_2instancia');
                                    $total = $valor1 + $valor2;

                                    $set('saldo_total', $total);
                                })
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('saldo_total')
                                ->label('Saldo Total')
                                ->inlineLabel()
                                ->prefix('R$')
                                ->numeric()
                                ->inputMode('decimal')
                                ->columnSpan(1),
                        ])
                        ->columnSpan(2),
                ])
                ->columns(6)
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormFields()
            )
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('natureza.descricao')
                    ->label('Natureza de Despesas')
                    ->description(fn(Orcamento $record): string => $record->natureza->codigo)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('plano.descricao')
                    ->label('Plano Orçamentário')
                    ->wrap()
                    ->description(fn(Orcamento $record): string => $record->plano->codigo)
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ano')
                    ->label('Ano')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('previsao_total')
                    ->label('Previsão Total')
                    ->money('BRL')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Aprovado Total')
                    ->money('BRL')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo_total')
                    ->label('Saldo Atual')
                    ->money('BRL')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('Ano')
                    ->options(fn() => Orcamento::select('ano')->distinct()->pluck('ano', 'ano'))
                    ->default(date('Y'))
                    ->attribute('ano')
                    ->columnSpan(6),

                SelectFilter::make('Natureza')
                    ->relationship(
                        name: 'natureza',
                        titleAttribute: 'descricao'
                    )
                    ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->codigo}: {$record->descricao}")
                    ->searchable(['descricao'])
                    ->preload()
                    ->attribute('natureza_id')
                    ->columnSpan(6),

                SelectFilter::make('Plano Orçamentário')
                    ->relationship(
                        name: 'plano',
                        titleAttribute: 'descricao'
                    )
                    ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->codigo}: {$record->descricao}")
                    ->searchable(['descricao'])
                    ->preload()
                    ->attribute('planoorcamentario_id')
                    ->columnSpan(6),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),

                Action::make('duplicar')
                    ->hiddenLabel()
                    ->icon('heroicon-o-document-duplicate')
                    ->size('xl')
                    ->tooltip('Duplicar')
                    ->modalHeading('Duplicar orçamento')
                    ->modalWidth('7xl')
                    ->fillForm(fn(Orcamento $record) => [
                        'setor_id' => $record->setor_id,
                        'natureza_id' => $record->natureza_id,
                        'planoorcamentario_id' => $record->planoorcamentario_id,
                        'descricao' => $record->descricao,
                        'ano' => $record->ano,
                        'previsao_1instancia' => $record->previsao_1instancia,
                        'previsao_2instancia' => $record->previsao_2instancia,
                        'previsao_total' => $record->previsao_total,
                        'valor_1instancia' => $record->valor_1instancia,
                        'valor_2instancia' => $record->valor_2instancia,
                        'valor_total' => $record->valor_total,
                        'saldo_1instancia' => $record->saldo_1instancia,
                        'saldo_2instancia' => $record->saldo_2instancia,
                        'saldo_total' => $record->saldo_total,
                        'observacao' => $record->observacao,
                    ])
                    ->form(OrcamentoResource::getFormfields())
                    ->action(function (array $data) {
                        $novo = Orcamento::create([
                            'setor_id' => $data['setor_id'],
                            'natureza_id' => $data['natureza_id'],
                            'planoorcamentario_id' => $data['planoorcamentario_id'],
                            'descricao' => $data['descricao'],
                            'ano' => $data['ano'],
                            'previsao_1instancia' => $data['previsao_1instancia'],
                            'previsao_2instancia' => $data['previsao_2instancia'],
                            'previsao_total' => $data['previsao_total'],
                            'valor_1instancia' => $data['valor_1instancia'],
                            'valor_2instancia' => $data['valor_2instancia'],
                            'valor_total' => $data['valor_total'],
                            'saldo_1instancia' => $data['saldo_1instancia'],
                            'saldo_2instancia' => $data['saldo_2instancia'],
                            'saldo_total' => $data['saldo_total'],
                            'observacao' => $data['observacao'],
                        ]);
                        Notification::make()
                            ->title("Orçamento gerado corretamente!")
                            ->success()
                            ->send();
                    }),

                Action::make('despesas')
                    ->hiddenLabel()
                    ->size('xl')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->tooltip('Eventos do Orçamento')
                    ->url(fn($record) => route('filament.admin.orcamento.resources.contratacao.orcamento-eventos.index', [
                        'orcamento_id' => $record->id,
                    ])),

                ViewAction::make('view_eventos')
                    ->hiddenLabel()
                    ->size('xl')
                    ->icon('heroicon-o-queue-list')
                    ->modalWidth('7xl')
                    ->infolist([
                        Grid::make(4)
                            ->columns(4)
                            ->extraAttributes([])
                            ->schema([
                                Infolists\Components\TextEntry::make('ano')
                                    ->label('Ano'),

                                Infolists\Components\TextEntry::make('descricao')
                                    ->label('Orçamento'),

                                Infolists\Components\TextEntry::make('natureza.descricao')
                                    ->label('Natureza'),

                                Infolists\Components\TextEntry::make('plano.descricao')
                                    ->label('Curso'),

                                Infolists\Components\TextEntry::make('valor_1instancia')
                                    ->label('Valor 1º Instância'),

                                Infolists\Components\TextEntry::make('valor_2instancia')
                                    ->label('Valor 2º Instância'),

                                Infolists\Components\TextEntry::make('valor_total')
                                    ->label('Valor Total')
                                    ->columnSpan(2),
                            ]),

                        Section::make('Eventos')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('eventos')
                                    ->hiddenLabel()
                                    // ->grid(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tipo.descricao')
                                            ->inlineLabel()
                                            ->label('Tipo Evento'),

                                        Infolists\Components\TextEntry::make('descricao')
                                            ->inlineLabel()
                                            ->label('Descrição'),

                                        Infolists\Components\TextEntry::make('valor_1instancia')
                                            ->label('Valor 1º Instância'),

                                        Infolists\Components\TextEntry::make('valor_2instancia')
                                            ->label('Valor 2º Instância'),

                                        Infolists\Components\TextEntry::make('valor_total')
                                            ->label('Valor Total')
                                            ->columnSpan(2),
                                    ]),
                            ]),

                        Section::make('Processos')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('reservas')
                                    ->hiddenLabel()
                                    // ->grid(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('processo.numero')
                                            ->inlineLabel()
                                            ->label('Processo'),

                                        Infolists\Components\TextEntry::make('processo.curso.titulo')
                                            ->inlineLabel()
                                            ->label('Curso'),

                                        Infolists\Components\TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        Infolists\Components\TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Reserva'),

                                        Infolists\Components\TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),

                        Section::make('Empenhos')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('empenhos')
                                    ->hiddenLabel()
                                    // ->grid(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('processo.numero')
                                            ->inlineLabel()
                                            ->label('Processo'),

                                        Infolists\Components\TextEntry::make('processo.curso.titulo')
                                            ->inlineLabel()
                                            ->label('Curso'),

                                        Infolists\Components\TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        Infolists\Components\TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Empenho'),

                                        Infolists\Components\TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),

                        Section::make('Liquidação')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('liquidacoes')
                                    ->hiddenLabel()
                                    // ->grid(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('processo.numero')
                                            ->inlineLabel()
                                            ->label('Processo'),

                                        Infolists\Components\TextEntry::make('processo.curso.titulo')
                                            ->inlineLabel()
                                            ->label('Curso'),

                                        Infolists\Components\TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        Infolists\Components\TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Liquidação'),

                                        Infolists\Components\TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),

                        Section::make('OS/Fornecimento')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('ordemservicos')
                                    ->hiddenLabel()
                                    // ->grid(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('processo.numero')
                                            ->inlineLabel()
                                            ->label('Processo'),

                                        Infolists\Components\TextEntry::make('processo.curso.titulo')
                                            ->inlineLabel()
                                            ->label('Curso'),

                                        Infolists\Components\TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        Infolists\Components\TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Serviço'),

                                        Infolists\Components\TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),
                    ]),
            ])
            ->toolBarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrcamentos::route('/'),
            'create' => Pages\CreateOrcamento::route('/create'),
            'edit' => Pages\EditOrcamento::route('/{record}/edit'),
        ];
    }
}
