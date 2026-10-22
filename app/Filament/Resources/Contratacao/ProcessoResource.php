<?php

namespace App\Filament\Resources\Contratacao;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\Contratacao\ProcessoCluster;
use App\Filament\Resources\Cadastro\FornecedorResource;
use App\Filament\Resources\Contratacao\ProcessoResource\Pages;
use App\Filament\Resources\Contratacao\ProcessoResource\RelationManagers;
use App\Filament\Resources\Escola\CursosResource;
use App\Filament\Resources\UserResource;
use App\Models\egap\Cadastro\Fornecedores;
use App\Models\Contratacao\Processo;
use App\Models\Escola\Cursos;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Collection;

class ProcessoResource extends Resource
{
    protected static ?string $model = Processo::class;
    protected static ?string $cluster = ProcessoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'numero';
    protected static ?string $modelLabel = 'Processo';
    protected static ?string $puralModelLabel = 'Processos ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Processos';
    protected static ?int $navigationSort = 1;

    public static function getFormfields(): array
    {
        return [
            Section::make('Processo')
                ->schema([
                    Forms\Components\TextInput::make('numero')
                        ->label('Número do Processo')
                        ->inlineLabel()
                        ->required()
                        ->mask('9999999-99.9999.9.99.9999')
                        ->columnSpan(4),

                    Forms\Components\Select::make('tipoprocesso_id')
                        ->label('Tipo de Processo')
                        ->inlineLabel()
                        ->relationship('tipo', 'descricao')
                        ->searchable()
                        ->preload()
                        ->createOptionForm(
                            TipoProcessoResource::getFormfields()
                        )
                        ->columnSpan(4),

                    Forms\Components\Select::make('modalidade_id')
                        ->label('Modalidade da Contratação')
                        ->relationship('modalidade', 'descricao')
                        ->searchable()
                        ->preload()
                        ->columnSpan(4),

                    Forms\Components\RichEditor::make('descricao')
                        ->label('Descrição')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->columnSpan(3),

            Tabs::make('Contratação')
                ->columnSpan(3)
                ->columns(4)
                ->tabs([
                    Tab::make('Contratação')
                        ->icon('heroicon-m-credit-card')
                        ->schema([
                            Forms\Components\Radio::make('fisica_juridica')
                                ->label('Pessoa')
                                ->inlineLabel()
                                ->options([
                                    'F' => 'Física',
                                    'J' => 'Jurídica',
                                ])
                                ->inline()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('fornecedor_id')
                                ->label('Fornecedor')
                                ->inlineLabel()
                                ->relationship('fornecedor', 'razao_social')
                                ->searchable()
                                ->preload()
                                ->createOptionForm(
                                    FornecedorResource::getFormfields()
                                )
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Gestores')
                        ->icon('heroicon-m-user-group')
                        ->schema([

                            Forms\Components\Repeater::make('gestores')
                                ->hiddenLabel()
                                ->schema([
                                    Forms\Components\Select::make('gestor_id')
                                        ->label('Gestor')
                                        ->inlineLabel()
                                        ->multiple()
                                        ->relationship('gestores', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm(
                                            UserResource::getFormFields()
                                        )
                                        ->columnSpan(3),

                                    Forms\Components\Toggle::make('principal')
                                        ->label('Principal')
                                        ->inline()
                                        ->inlineLabel(false)
                                        ->columnSpan(1),
                                ])
                                ->addActionLabel('Adicionar Gestor')
                                ->columns(4)
                                ->columnSpanFull(),

                        ]),

                    Tab::make('Cursos')
                        ->icon(CursosResource::getNavigationIcon())
                        ->schema([

                            Forms\Components\Repeater::make('cursos')
                                ->hiddenLabel()
                                ->collapsible()
                                // ->itemLabel(fn(array $state): ?string => $state['curso.titulo'] ?? null)
                                ->schema([
                                    Forms\Components\Select::make('curso_id')
                                        ->label('Curso')
                                        ->options(
                                            Cursos::where('inicio', '>', now())->pluck('titulo', 'id')
                                        )
                                        ->getOptionLabelFromRecordUsing(fn(Model $record) => ($record->curso_id) ? "Curso: {$record->titulo}" : "Atividade: {$record->titulo}")
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('instrutor_id')
                                        ->label('Instrutor')
                                        ->options(
                                            User::pluck('name', 'id')
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm(
                                            UserResource::getFormfieldsCadastro()
                                        )
                                        ->columnSpan(2),

                                ])
                                ->addActionLabel('Adicionar Curso')
                                ->columns(4)
                                ->columnSpanFull(),

                        ]),
                ]),
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
                Tables\Columns\TextColumn::make('numero')
                    ->label('Número do Processo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->html()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo.descricao')
                    ->label('Tipo de Processo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fornecedor_instrutor')
                    ->label('Fornecedor/Instrutor')
                    // ->searchable()
                    ->sortable()
                    ->state(function (Processo $record) {
                        $nome = '';
                        if (isset($record->fornecedor->name))
                            $nome = $record->fornecedor->name;
                        if (isset($record->instrutor->name))
                            $nome = $record->instrutor->name;
                        return $nome;
                    }),

                Tables\Columns\TextColumn::make('gestores.name')
                    ->label('Gestores')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('processo_eventos_count')
                    ->label('Eventos')
                    ->badge()
                    ->counts('processo_eventos'),

                Tables\Columns\ToggleColumn::make('ativo'),
            ])
            ->filters([
                Tables\Filters\Filter::make('ativo')
                    ->toggle()
                    ->default()
                    ->query(fn(Builder $query): Builder => $query->where('ativo', 1)),

                Tables\Filters\SelectFilter::make('Tipo Processo')
                    ->relationship(
                        name: 'tipo',
                        titleAttribute: 'descricao',
                    )
                    // ->searchable(['descricao'])
                    ->preload()
                    ->attribute('tipoprocesso_id')
                    ->columnSpan(6),

                Tables\Filters\SelectFilter::make('fornecedor')
                    ->relationship(
                        name: 'fornecedor',
                        titleAttribute: 'razao_social',
                    )
                    ->searchable(['razao_social'])
                    ->preload()
                    ->attribute('fornecedor_id')
                    ->columnSpan(6),

                Tables\Filters\SelectFilter::make('gestores')
                    ->label('Gestores')
                    ->multiple()
                    ->options(
                        User::pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
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
            ->headerActions(
                ProcessoEventoResource::getHeaderActions()
            )
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel(),

                ViewAction::make('view')
                    ->hiddenLabel(),

                DeleteAction::make()
                    ->hiddenLabel(),

                Action::make('eventos')
                    ->hiddenLabel()
                    ->size('xl')
                    ->icon('heroicon-o-banknotes')
                    // ->color('danger')
                    ->tooltip('Eventos do processo: Reserva, Empenho, Liquidação e Ordem de Serviço')
                    ->url(fn($record) => route('filament.admin.processo.resources.contratacao.processo-eventos.index', [
                        'processo_id' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

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
                                TextEntry::make('tipo.descricao')
                                    ->label('Tipo de Contratação'),

                                TextEntry::make('curso.titulo')
                                    ->label('Curso'),

                                TextEntry::make('instrutor.name')
                                    ->label('Instrutor'),
                            ]),

                        Section::make('Reservas')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                RepeatableEntry::make('reservas')
                                    ->hiddenLabel()
                                    ->grid(4)
                                    ->schema([
                                        TextEntry::make('orcamento.natureza.codigo')
                                            ->inlineLabel()
                                            ->label('Orcamento'),


                                        TextEntry::make('instancia')
                                            ->inlineLabel()
                                            ->label('InstÃ¢ncia')
                                            ->getStateUsing(fn($record) => "{$record->instancia}Âª InstÃ¢ncia"),

                                        TextEntry::make('numero')
                                            ->inlineLabel()
                                            ->label('Número'),

                                        TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Reserva'),

                                        TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),

                        Section::make('Empenhos')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                RepeatableEntry::make('empenhos')
                                    ->hiddenLabel()
                                    ->grid(4)
                                    ->schema([
                                        TextEntry::make('orcamento.natureza.codigo')
                                            ->inlineLabel()
                                            ->label('Orcamento'),


                                        TextEntry::make('instancia')
                                            ->inlineLabel()
                                            ->label('InstÃ¢ncia')
                                            ->getStateUsing(fn($record) => "{$record->instancia}Âª InstÃ¢ncia"),

                                        TextEntry::make('numero')
                                            ->inlineLabel()
                                            ->label('Número'),

                                        TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Empenho'),

                                        TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),

                        Section::make('Liquidação')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                RepeatableEntry::make('liquidacoes')
                                    ->hiddenLabel()
                                    ->grid(4)
                                    ->schema([
                                        TextEntry::make('orcamento.natureza.codigo')
                                            ->inlineLabel()
                                            ->label('Orcamento'),


                                        TextEntry::make('instancia')
                                            ->inlineLabel()
                                            ->label('Instância')
                                            ->getStateUsing(fn($record) => "{$record->instancia}ª Instância"),

                                        TextEntry::make('numero')
                                            ->inlineLabel()
                                            ->label('Número'),

                                        TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Liquidação'),

                                        TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),

                        Section::make('OS/Fornecimento')
                            ->collapsible()
                            ->columnSpanFull()
                            ->schema([
                                RepeatableEntry::make('ordemservicos')
                                    ->hiddenLabel()
                                    ->grid(4)
                                    ->schema([
                                        TextEntry::make('orcamento.natureza.codigo')
                                            ->inlineLabel()
                                            ->label('Orcamento'),


                                        TextEntry::make('instancia')
                                            ->inlineLabel()
                                            ->label('Instância')
                                            ->getStateUsing(fn($record) => "{$record->instancia}ª Instância"),

                                        TextEntry::make('numero')
                                            ->inlineLabel()
                                            ->label('Número'),

                                        TextEntry::make('data')
                                            ->inlineLabel()
                                            ->label('Data'),

                                        TextEntry::make('numero_documento')
                                            ->inlineLabel()
                                            ->label('Nota Reserva'),

                                        TextEntry::make('valor')
                                            ->inlineLabel()
                                            ->label('Valor')
                                            ->money('BRL'),
                                    ]),
                            ]),
                    ]),

            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),
                BulkAction::make('Ativar/Desativar')
                    ->action(function (Collection $records) {
                        foreach ($records as $row) {
                            $row->ativo = !$row->ativo;
                            $row->save();
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                // ]),
            ])
            ->persistFiltersInSession()
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProcessoEventosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcessos::route('/'),
            'create' => Pages\CreateProcesso::route('/create'),
            'edit' => Pages\EditProcesso::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->numero;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'numero',
            'descricao',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Processo' => $record->numero,
            'Descrição' => strip_tags($record->descricao),
        ];
    }
}
