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
use Filament\Schemas\Components\Grid;
use App\Filament\Clusters\Contratacao\OrcamentoCluster;
use App\Filament\Resources\Cadastro\NaturezaDespesaResource;
use App\Filament\Resources\Contratacao\OrcamentoEventoResource\Pages;
use App\Filament\Resources\OrcamentoEventoResource\RelationManagers;
use App\Models\Contratacao\Orcamento;
use App\Models\Contratacao\OrcamentoEvento;
use App\Models\Cadastro\Tabelas;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrcamentoEventoResource extends Resource
{
    protected static ?string $model = OrcamentoEvento::class;
    protected static ?string $cluster = OrcamentoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Orçamento - Evento';
    protected static ?string $puralModelLabel = 'Orçamento - Evento';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Orçamento - Eventos';
    // protected static string | UnitEnum | null $navigationGroup = 'Orçamento';
    protected static ?int $navigationSort = 2;
    public static function somarValor($set, $get)
    {
        $valor1 = (float) $get('valor_1instancia');
        $valor2 = (float) $get('valor_2instancia');
        $total = $valor1 + $valor2;

        $set('valor_total', $total);
        return $total;
    }
    public static function criarRemanejamento(array $data)
    {
        // dd($data);
        try {
            DB::beginTransaction();

            $destino_id = $data['destino_id'];
            $destino = Orcamento::find($destino_id);

            $saldo1 = 0;
            $saldo2 = 0;
            $stotal = 0;

            $naturezas = $data['natureza_despesa'];
            foreach ($naturezas as $natureza) {
                $valor1 = 0;
                $valor2 = 0;
                $vtotal = 0;

                $origem_id = $natureza['orcamento_id'];
                $origem = Orcamento::find($origem_id);

                $valor1 = (float) $natureza['valor_1instancia'];
                $valor2 = (float) $natureza['valor_2instancia'];
                $vtotal = $valor1 + $valor2;

                $saldo1 += $valor1;
                $saldo2 += $valor2;
                $stotal += $vtotal;

                $evento = new OrcamentoEvento();
                $evento->natureza_id = $origem['natureza_id'];
                $evento->tipoevento_id = config('settings.orcamentoevento.decrescimo'); // decréscimo
                $evento->descricao = 'Remanejamento entre Contas: Decréscimo';
                $evento->observacao = $data['observacao'];
                $evento->valor_1instancia = $valor1;
                $evento->valor_2instancia = $valor2;
                $evento->valor_total = $vtotal;
                $evento->atualizado_por = Auth::user()->id;
                $evento->save();
            }

            $evento = new OrcamentoEvento();
            $evento->natureza_id = $destino['natureza_id'];
            $evento->tipoevento_id = config('settings.orcamentoevento.acrescimo'); // acréscimo
            $evento->descricao = 'Remanejamento entre Contas: Acréscimo';
            $evento->observacao = $data['observacao'];
            $evento->valor_1instancia = $saldo1;
            $evento->valor_2instancia = $saldo2;
            $evento->valor_total = $stotal;
            $evento->atualizado_por = Auth::user()->id;
            $evento->save();

            DB::commit();

            Notification::make()
                ->title('Remanejamento cadastrado corretamente!')
                ->success()
                ->send();
        } catch (Halt $e) {
            Notification::make()
                ->title('ERRO!' . $e)
                ->danger()
                ->send();
        }
    }
    public static function getFormfieldsRemanejamento(): array
    {
        return [
            Forms\Components\Repeater::make('natureza_despesa')
                ->label('Retirar de')
                ->addActionLabel('Adicionar')
                ->schema([
                    Grid::make()
                        ->schema([
                            Forms\Components\Select::make('orcamento_id')
                                ->label('Orçamento')
                                ->required()
                                ->options(function (): array {
                                    $ano = date('Y');
                                    $orcamentos = Orcamento::where('ano', $ano)->get();
                                    $result = [];
                                    foreach ($orcamentos as $orcamento) {
                                        $result[$orcamento->id] = $orcamento->natureza->codigo . ": " . $orcamento->descricao;
                                    }
                                    return $result;
                                })
                                ->searchable(['descricao'])
                                // ->preload()
                                ->live(debounce: 800)
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $record = Orcamento::find($state);
                                    if ($record) {
                                        $valor1 = $record->saldo_1instancia;
                                        $valor2 = $record->saldo_2instancia;

                                        $set('valor_1instancia', $valor1);
                                        $set('valor_2instancia', $valor2);
                                    }
                                })
                                ->columnSpan(4)
                                ->createOptionForm(
                                    OrcamentoResource::getFormFields()
                                ),

                            Forms\Components\TextInput::make('valor_1instancia')
                                ->label('Valor 1Âª InstÃ¢ncia')
                                ->prefix('R$')
                                ->required()
                                ->columnSpan(2)
                                ->numeric()
                                ->default(0.00)
                                ->inputMode('decimal'),

                            Forms\Components\TextInput::make('valor_2instancia')
                                ->label('Valor 2ª Instância')
                                ->prefix('R$')
                                ->required()
                                ->columnSpan(2)
                                ->numeric()
                                ->default(0.00)
                                ->inputMode('decimal'),
                        ])
                        ->columns(8)
                        ->columnSpan(6),
                ])
                ->columns(6),

            Section::make('Acrescentar em')
                ->columns(4)
                ->schema([
                    Forms\Components\Select::make('destino_id')
                        ->label('Orçamento')
                        ->required()
                        ->options(function (): array {
                            $ano = date('Y');
                            $orcamentos = Orcamento::where('ano', $ano)->get();
                            $result = [];
                            foreach ($orcamentos as $orcamento) {
                                $result[$orcamento->id] = $orcamento->natureza->codigo . ": " . $orcamento->descricao;
                            }
                            return $result;
                        })
                        ->searchable(['descricao'])
                        // ->preload()
                        ->columnSpan(3)
                        ->createOptionForm(
                            OrcamentoResource::getFormFields()
                        ),
                ]),

            Forms\Components\RichEditor::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
        ];
    }
    public static function getFormfields(): array
    {
        return [
            Section::make('Evento Orçamentário')
                ->description('Dados do Evento')
                ->schema([
                    Forms\Components\Select::make('orcamento_id')
                        // ->relationship(
                        //     name: 'orcamento',
                        //     titleAttribute: 'descricao',
                        //     // modifyQueryUsing: fn (Builder $query) => $query->where('ano','=',date('Y')),
                        // )
                        ->options(function () {
                            $orcamentos = Orcamento::where('ano', '=', date('Y'))->get();
                            $output = [];
                            foreach ($orcamentos as $orcamento) {
                                $output[$orcamento->id] = "{$orcamento->natureza->descricao} {$orcamento->natureza->codigo} - R$ {$orcamento->saldo_total}";
                            }
                            return $output;
                        })
                        ->searchable([''])
                        ->label('Orçamento')
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('tipoevento_id')
                        // ->relationship(
                        //     name: 'tipo',
                        //     titleAttribute: 'descricao',
                        //     modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'ORCAMENTO_EVENTO'),
                        // )
                        ->options(
                            Tabelas::where('grupo', '=', 'ORCAMENTO_EVENTO')->whereNot('id', 1802)->pluck('descricao', 'id')
                        )
                        ->searchable(['codigo', 'descricao'])
                        ->label('Tipo Evento')
                        ->preload()
                        ->required()
                        ->createOptionForm(
                            fn(Form $form) => NaturezaDespesaResource::form($form)
                        )
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('descricao')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('processo')
                        ->label('Nº Processo')
                        ->mask('99999-99.9999.9.99.9999')
                        ->columnSpan(2),

                    Forms\Components\DatePicker::make('data')
                        ->label('Data')
                        ->columnSpan(2),
                ])
                ->columns(6)
                ->columnSpan(4),

            Section::make('Valor Orçamentária')
                ->description('Dados solicitados')
                ->schema([
                    Forms\Components\TextInput::make('valor_1instancia')
                        ->label('Valor 1ª Instância')
                        ->inlineLabel()
                        ->required()
                        ->prefix('R$')
                        ->numeric()
                        ->inputMode('decimal')
                        ->live(debounce: 800)
                        ->afterStateUpdated(function (Set $set, Get $get) {
                            return static::somarValor($set, $get);
                        })
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('valor_2instancia')
                        ->label('Valor 2ª Instância')
                        ->inlineLabel()
                        ->required()
                        ->prefix('R$')
                        ->numeric()
                        ->inputMode('decimal')
                        ->live(debounce: 800)
                        ->afterStateUpdated(function (Set $set, Get $get) {
                            return static::somarValor($set, $get);
                        })
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('valor_total')
                        ->label('Valor Total')
                        ->inlineLabel()
                        ->required()
                        ->prefix('R$')
                        ->numeric()
                        ->inputMode('decimal')
                        ->columnSpan(1),
                ])
                ->columnSpan(2),

            Forms\Components\RichEditor::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('orcamento.natureza.descricao')
                    ->label('Natureza')
                    ->description(fn(OrcamentoEvento $record): string => $record->orcamento->natureza->codigo)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo.descricao')
                    ->label('Tipo Evento')
                    ->searchable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(),

                Tables\Columns\TextColumn::make('valor_1instancia')
                    ->label('Valor 1ª Instância')
                    ->money('BRL')
                    ->badge()
                    ->color('warning')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor_2instancia')
                    ->label('Valor 2ª Instância')
                    ->money('BRL')
                    ->badge()
                    ->color('success')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('ano_orcamento')
                    ->label('Ano do Orçamento')
                    ->options(
                        fn() => Orcamento::query()
                            ->select('ano')
                            ->distinct()
                            ->orderBy('ano', 'desc')
                            ->pluck('ano', 'ano')
                    )
                    ->default(date('Y'))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            return $query->whereHas('orcamento', function (Builder $q) use ($data) {
                                $q->where('ano', '=', $data['value']);
                            });
                        }
                    }),
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
            ->headerActions([])
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
            'index' => Pages\ListOrcamentoEventos::route('/'),
            'create' => Pages\CreateOrcamentoEvento::route('/create'),
            'edit' => Pages\EditOrcamentoEvento::route('/{record}/edit'),
        ];
    }
}
