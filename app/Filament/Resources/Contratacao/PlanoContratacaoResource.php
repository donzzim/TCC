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
use App\Filament\Resources\Cadastro\NaturezaDespesaResource;
use App\Filament\Resources\Contratacao\PlanoContratacaoResource\Pages;
use App\Filament\Resources\Contratacao\PlanoContratacaoResource\RelationManagers;
use App\Models\egap\Cadastro\NaturezaDespesa;
use App\Models\Contratacao\EtapaContratacao;
use App\Models\Contratacao\PlanoContratacao;
use App\Models\Contratacao\PlanoOrcamentario;
use App\Models\Planejamento\IniciativasEstrategicas;
use App\Models\Planejamento\ObjetivosEstrategicos;
use App\Models\Setores;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanoContratacaoResource extends Resource
{
    protected static ?string $model = PlanoContratacao::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $recordTitleAttribute = 'ano_execucao';
    protected static ?string $modelLabel = 'Plano de Contratação ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Orçamentário';
    protected static ?string $puralModelLabel = 'Plano de Contratação ';
    protected static ?string $navigationLabel = 'Plano de Contratação ';
    protected static ?int $navigationSort = 2;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('solicitante_id')
                ->label('Solicitante')
                ->relationship('solicitante', 'name')
                ->preload()
                ->searchable(['name'])
                // ->createOptionForm(
                //     PlanejamentoEstrategicoResource::getFormFields()
                // )
                ->required()
                ->columnSpan(2),

            Forms\Components\Select::make('unidade_gestora')
                ->label('Unidade Gestora')
                ->options([
                    'TJ' => 'TJ',
                    'FUNEPJ' => 'FUNEPJ',
                ])
                ->required()
                ->columnSpan(1),

            Forms\Components\Select::make('classificacao')
                ->label('Classificação')
                ->options([
                    'Custeio' => 'Custeio',
                    'Investimento' => 'Investimento',
                ])
                ->required()
                ->columnSpan(1),

            Forms\Components\TextInput::make('ano_execucao')
                ->label('Ano Execução')
                ->required()
                ->numeric()
                ->maxLength(4)
                ->columnSpan(1),

            Forms\Components\TextInput::make('valor_total')
                ->label('Valor Total')
                ->prefix('R$')
                ->numeric()
                ->inputMode('decimal')
                ->columnSpan(1),

            Forms\Components\Textarea::make('comentario')
                ->label('Comentário')
                ->columnSpanFull(),

            Section::make('Bens e Servicos')
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Repeater::make('bemServicos')
                        ->hiddenLabel()
                        ->columns(6)
                        ->columnSpanFull()
                        ->addActionLabel('Adicionar bens e serviços')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->collapsed()
                        ->cloneable()
                        ->itemLabel(fn(array $state): ?string => $state['nome'] ?? null)
                        ->extraAttributes(['class' => 'm-0 p-0'])
                        ->schema([

                            Section::make('Ação')
                                ->columns(2)
                                ->columnSpanFull()
                                ->compact()
                                ->schema([
                                    Forms\Components\TextInput::make('nome')
                                        ->label('Descrição')
                                        ->inlineLabel()
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\Textarea::make('comentario')
                                        ->label('Comentário')
                                        ->inlineLabel()
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Planejamento')
                                ->columns(2)
                                ->columnSpan(2)
                                ->compact()
                                ->extraAttributes(['class' => 'h-64'])
                                ->schema([
                                    Forms\Components\Select::make('objetivo_id')
                                        // ->relationship('natureza', 'descricao')
                                        ->label('Objetivo Estratégico')
                                        ->inlineLabel()
                                        ->options(ObjetivosEstrategicos::pluck('objetivo', 'id'))
                                        ->preload()
                                        ->searchable(['objetivo'])
                                        // ->createOptionForm(
                                        //     NaturezaDespesaResource::getFormFields()
                                        // )
                                        ->required()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('iniciativa_id')
                                        // ->relationship('natureza', 'descricao')
                                        ->label('Iniciativa Estratégica')
                                        ->inlineLabel()
                                        ->options(IniciativasEstrategicas::pluck('iniciativa', 'id'))
                                        ->preload()
                                        ->searchable(['iniciativa'])
                                        // ->createOptionForm(
                                        //     NaturezaDespesaResource::getFormFields()
                                        // )
                                        ->required()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('classificacao')
                                        ->label('Projeto/Atividade')
                                        ->inlineLabel()
                                        ->options([
                                            'Projeto' => 'Projeto',
                                            'Atividade' => 'Atividade',
                                        ])
                                        ->required()
                                        ->columnSpan(2),
                                ]),

                            Section::make('Orçamento')
                                ->columns(2)
                                ->columnSpan(2)
                                ->compact()
                                ->extraAttributes(['class' => 'h-64'])
                                ->schema([
                                    Forms\Components\Select::make('natureza_id')
                                        // ->relationship('natureza', 'descricao')
                                        ->label('Natureza')
                                        ->inlineLabel()
                                        ->options(NaturezaDespesa::pluck('codigo', 'id'))
                                        ->preload()
                                        ->searchable(['descricao'])
                                        // ->createOptionForm(
                                        //     NaturezaDespesaResource::getFormFields()
                                        // )
                                        ->required()
                                        ->columnSpan(3),

                                    Forms\Components\Select::make('planoorcamentario_id')
                                        // ->relationship('natureza', 'descricao')
                                        ->label('Plano Orçamentario')
                                        ->inlineLabel()
                                        ->options(PlanoOrcamentario::pluck('codigo', 'id'))
                                        ->preload()
                                        ->searchable(['codigo'])
                                        // ->createOptionForm(
                                        //     NaturezaDespesaResource::getFormFields()
                                        // )
                                        ->required()
                                        ->columnSpan(3),
                                ]),

                            Section::make('Informações')
                                ->columns(3)
                                ->columnSpan(2)
                                ->compact()
                                ->extraAttributes(['class' => 'h-64'])
                                ->schema([
                                    Forms\Components\DatePicker::make('data_estimada')
                                        ->label('Data Estimada')
                                        ->columnSpan(1),

                                    Forms\Components\DatePicker::make('data_inicio')
                                        ->label('Início')
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('prioridade')
                                        ->label('Prioridade')
                                        ->options([
                                            'Baixa' => 'Baixa',
                                            'Média' => 'Média',
                                            'Alta' => 'Alta',
                                        ])
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('criterio')
                                        ->label('Critérios')
                                        ->options([
                                            'Não' => 'Não',
                                            'Ambos' => 'Ambos',
                                            'Sustentabilidade' => 'Sustentabilidade',
                                            'Acessibilidade' => 'Acessibilidade',
                                        ])
                                        ->required()
                                        ->columnSpan(1),

                                    Forms\Components\Toggle::make('renovacao')
                                        ->label('Renovação de Contrato?')
                                        ->inlineLabel(false)
                                        ->columnSpan(1),
                                ]),

                            Section::make('Valores')
                                ->columns(4)
                                ->columnSpanFull()
                                ->compact()
                                ->schema([
                                    Forms\Components\TextInput::make('quantidade')
                                        ->label('Quantidade')
                                        ->numeric()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('valor1')
                                        ->label('Valor 1º Instância')
                                        ->prefix('R$')
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('valor2')
                                        ->label('Valor 2ª Instância')
                                        ->prefix('R$')
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('valor_total')
                                        ->label('Valor Total')
                                        ->prefix('R$')
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->columnSpan(1),
                                ]),

                        ]),
                ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->schema(static::getFormFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ano_execucao')
                    ->label('Ano')
                    ->searchable(),

                Tables\Columns\TextColumn::make('classificacao')
                    ->label('Classificação')
                    ->searchable(),

                Tables\Columns\TextColumn::make('etapa.descricao')
                    ->label('Situação')
                    ->searchable(),

                Tables\Columns\TextColumn::make('setor.unidadeorganizacional')
                    ->label('Unidade')
                    ->searchable(),

                Tables\Columns\TextColumn::make('setor.setor')
                    ->label('Setor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('solicitante.name')
                    ->label('Solicitante')
                    ->searchable(),

            ])
            ->filters([

                SelectFilter::make('ano')
                    ->options(PlanoContratacao::distinct('ano_execucao')->pluck('ano_execucao', 'ano_execucao'))
                    ->preload()
                    ->attribute('ano_execucao')
                    ->columnSpan(1),

                SelectFilter::make('classificacao')
                    ->options([
                        'Custeio' => 'Custeio',
                        'Investimento' => 'Investimento',
                    ])
                    ->attribute('classificacao')
                    ->columnSpan(1),

                SelectFilter::make('prioridade')
                    ->options([
                        'Baixa' => 'Baixa',
                        'Média' => 'Média',
                        'Alta' => 'Alta',
                    ])
                    ->attribute('prioridade')
                    ->columnSpan(1),

                SelectFilter::make('criterio')
                    ->options([
                        'Não' => 'Não',
                        'Ambos' => 'Ambos',
                        'Sustentabilidade' => 'Sustentabilidade',
                        'Acessibilidade' => 'Acessibilidade',
                    ])
                    ->attribute('criterio')
                    ->columnSpan(1),

                // SelectFilter::make('etapa')
                //     ->options(EtapaContratacao::pluck('descricao', 'id'))
                //     ->searchable(['descricao'])
                //     ->preload()
                //     ->attribute('etapa_id')
                //     ->columnSpan(4),

                SelectFilter::make('setor')
                    ->options(Setores::pluck('setor', 'id'))
                    ->searchable(['setor'])
                    ->preload()
                    ->attribute('setor_id')
                    ->columnSpan(4),

                SelectFilter::make('solicitante')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(['name'])
                    ->preload()
                    ->attribute('solicitante_id')
                    ->columnSpan(4),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel(),
                ViewAction::make()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->hiddenLabel(),
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
            'index' => Pages\ListPlanoContratacaos::route('/'),
            'create' => Pages\CreatePlanoContratacao::route('/create'),
            'edit' => Pages\EditPlanoContratacao::route('/{record}/edit'),
        ];
    }
}
