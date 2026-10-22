<?php

namespace App\Filament\Resources\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Resources\Administrativo\RegistroFuncionalResource\Pages;
use App\Filament\Resources\Escola\CursoAtividadesResource;
use App\Filament\Resources\Escola\CursosResource;
use App\Filament\Resources\UserResource;
use App\Models\Escola\CursoAtividades;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Administrativo\TipoRegistroFuncional;
use App\Models\Lotacao;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Component as Livewire;

class RegistroFuncionalResource extends Resource
{
    protected static ?string $model = RegistroFuncional::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-newspaper';
    // protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Registro Funcional ';
    protected static ?string $puralModelLabel = 'Registro Funcional ';
    protected static ?string $navigationLabel = 'Registro Funcional ';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 6;

    public static function getFormfieldsEquipeTrabalho(): array
    {
        return [

            Forms\Components\Select::make('tipo_id')
                ->options(
                    TipoRegistroFuncional::all()->pluck('descricao', 'id')
                )
                ->searchable(['descricao'])
                ->label('Tipo')
                ->required()
                ->createOptionForm([
                    Forms\Components\Hidden::make('grupo')
                        ->default('REGISTRO_FUNCIONAL'),
                    Forms\Components\TextInput::make('descricao')
                        ->label('Descrição')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(255),
                ])
                ->columnSpan(1),

            Forms\Components\Select::make('pessoa_id')
                ->options(
                    Lotacao::usuariosSetorPrincipal()
                )
                ->searchable(['name'])
                ->label('Pessoa')
                ->required()
                ->preload()
                ->createOptionForm(
                    UserResource::getFormfieldsCadastro()
                )
                ->columnSpan(2),

            Forms\Components\DatePicker::make('inicio')
                ->label('Data inÃ­cio')
                ->required()
                ->default(date('Y-m-d'))
                ->columnSpan(1),

            Forms\Components\DatePicker::make('termino')
                ->label('Data Término')
                ->columnSpan(1),

            Forms\Components\TextInput::make('quantidade')
                ->label('Qtde Dias/Horas')
                ->default(1)
                ->suffix('horas')
                ->numeric()
                ->inputMode('decimal')
                ->columnSpan(1),
        ];
    }
    public static function getFormfields(): array
    {
        return [
            Section::make('Registro Funcional')
                ->schema([
                    Forms\Components\Select::make('tipo_id')
                        ->relationship(
                            name: 'tipo',
                            titleAttribute: 'descricao',
                            modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'REGISTRO_FUNCIONAL'),
                        )
                        ->label('Tipo')
                        ->required()
                        ->createOptionForm([
                            Forms\Components\Hidden::make('grupo')
                                ->default('REGISTRO_FUNCIONAL'),
                            Forms\Components\TextInput::make('descricao')
                                ->label('Descrição')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columnSpan(1),

                    Forms\Components\Select::make('pessoa_id')
                        ->relationship(name: 'pessoa', titleAttribute: 'name')
                        ->searchable(['name'])
                        ->label('Pessoa')
                        ->preload()
                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(2),

                    Forms\Components\Select::make('demanda_id')
                        ->relationship(
                            name: 'demanda',
                            titleAttribute: 'titulo',
                        )
                        ->searchable(['titulo'])
                        ->label('Demanda')
                        ->live()
                        ->preload()
                        ->createOptionForm(
                            DemandaResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\Select::make('curso_id')
                        ->relationship(
                            name: 'curso',
                            titleAttribute: 'titulo',
                        )
                        ->searchable(['titulo'])
                        ->label('Curso')
                        ->live()
                        ->preload()
                        // ->createOptionForm(
                        //     CursosResource::getFormFields()
                        // )
                        ->columnSpan(2),

                    Forms\Components\Select::make('atividade_id')
                        // ->relationship(
                        //     name: 'atividade',
                        //     titleAttribute: 'titulo',
                        // )
                        ->options(function (Get $get) {
                            return CursoAtividades::where('curso_id', $get('curso_id'))->pluck('titulo', 'id');
                        })
                        ->searchable(['titulo'])
                        ->label('Atividade')
                        ->preload()
                        // ->createOptionForm(
                        //     CursosResource::getFormFields()
                        // )
                        ->columnSpan(2),

                    Forms\Components\RichEditor::make('observacao')
                        ->label('Observação')
                        ->columnSpan(4),
                ])
                ->columnSpan(4)
                ->columns(4),

            Section::make('Período')
                ->schema([
                    Forms\Components\DateTimePicker::make('inicio')
                        ->label('Data início')
                        ->required()
                        ->default(date('Y-m-d H:i:m'))
                        ->columnSpan(1),

                    Forms\Components\DateTimePicker::make('termino')
                        ->label('Data término')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('quantidade')
                        ->label('Quantidade')
                        ->default(0)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('processo')
                        ->label('Núm. Processo')
                        ->mask('99999-99.9999.9.99.9999')
                        ->maxLength(255)
                        ->columnSpan(1),
                ])
                ->columnSpan(2)
                ->columns(2),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipo.descricao')
                    ->label('Tipo'),

                Tables\Columns\ImageColumn::make('pessoa.avatar_url')
                    ->label('Foto')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/admin-tie.png'))
                    ->circular(),

                Tables\Columns\TextColumn::make('pessoa.name')
                    ->label('Pessoa')
                    ->formatStateUsing(function (string $state) {
                        $words = explode(' ', $state);
                        return reset($words) . ' ' . end($words);
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('demanda.titulo')
                    ->label('Demanda')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('curso.titulo')
                //     ->label('Curso')
                //     ->description(fn(Cursos $record) => $record->titulo)
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('atividade.titulo')
                    ->label('Curso')
                    ->description(function (Model $record) {
                        if (isset($record->curso->titulo))
                            return $record->curso->titulo;
                        return;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inicio')
                    ->dateTime('d/m/Y H:i:s')
                    ->label('InÃ­cio')
                    ->sortable(),

                Tables\Columns\TextColumn::make('termino')
                    ->dateTime('d/m/Y H:i:s')
                    ->label('Término')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantidade')
                    ->label('Quantidade')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('situacao')
                    ->label('Realizado')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('Realizado')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('situacao', 1)),

                SelectFilter::make('Tipo')
                    ->relationship(
                        name: 'tipo',
                        titleAttribute: 'descricao',
                        modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'REGISTRO_FUNCIONAL'),
                    )
                    ->searchable(['descricao'])
                    ->multiple()
                    ->preload()
                    ->columnSpan(8),

                SelectFilter::make('Pessoa')
                    ->relationship(
                        name: 'pessoa',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    ->preload()
                    ->columnSpan(8),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(8)
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
                    ->hiddenLabel(),
                ViewAction::make()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->hiddenLabel(),
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListRegistroFuncionals::route('/'),
            'create' => Pages\CreateRegistroFuncional::route('/create'),
            'edit' => Pages\EditRegistroFuncional::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->tipo->descricao;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'tipo.descricao',
            'pessoa.name',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Tipo' => $record->tipo->descricao,
            'Pessoa' => $record->pessoa->name,
            'Data' => Carbon::parse($record->inicio)->format("Y/m/d H:i:s"),
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['tipo', 'pessoa']);
    }
}
