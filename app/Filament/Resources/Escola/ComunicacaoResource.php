<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Escola\ComunicacaoResource\Pages;
use App\Http\Controllers\PrincipalController;

use App\Models\Escola\Comunicacao as EscolaComunicacao;
use App\Models\Escola\Cursos;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
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

class ComunicacaoResource extends Resource
{
    protected static ?string $model = EscolaComunicacao::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $recordTitleAttribute = 'tipo.titulo';
    protected static ?string $modelLabel = 'Comunicação ';
    protected static ?string $puralModelLabel = 'Comunicações ';
    protected static ?string $navigationLabel = 'Comunicações';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 7;
    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('tipo.titulo')
                ->label('Tipo')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('meio.descricao')
                ->label('Meio')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('publico.descricao')
                ->label('Público Alvo')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('texto')
                ->label('Texto')
                ->searchable()
                ->html()
                ->sortable(),

            Tables\Columns\TextColumn::make('cursos.titulo')
                ->label('Curso')
                ->searchable()
                ->sortable()
                ->badge()
                ->wrap()
                ->separator(','),
        ];
    }
    public static function getFormfields(): array
    {
        return [
            Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('Comunicação')
                        ->schema([
                            Forms\Components\Select::make('tipocomunicacao_id')
                                ->label('Tipo')
                                ->relationship('tipo', 'titulo')
                                ->searchable()
                                ->required()
                                ->preload()
                                ->createOptionForm(
                                    ComunicacaoTipoResource::getFormFields()
                                )
                                ->columnSpan(2),

                            Forms\Components\Select::make('meiocomunicacao_id')
                                ->label('Meio')
                                ->relationship(
                                    'meio',
                                    'descricao',
                                    modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'MEIO_COMUNICACAO'),
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\Select::make('publico_id')
                                ->label('Público Alvo')
                                ->relationship(
                                    'publico',
                                    'descricao',
                                    modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'PUBLICO_ALVO'),
                                )
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\Select::make('cursos')
                                ->label('Cursos')
                                ->multiple()
                                ->relationship(
                                    name: 'cursos',
                                    titleAttribute: 'titulo',
                                    modifyQueryUsing: fn(Builder $query) => $query->select('esc_cursos.*')
                                )
                                ->searchable()
                                ->preload()
                                ->createOptionForm(
                                    CursosResource::getFormFields()
                                )
                                ->columnSpanFull(),

                            // Forms\Components\Select::make('atividades')
                            //     ->label('Atividades')
                            //     ->multiple()
                            //     ->relationship('atividades', 'inicio')
                            //     ->searchable()
                            //     ->preload()
                            //     ->createOptionForm(
                            //         CursoAtividadesResource::getFormFields()
                            //     )
                            //     ->columnSpan(2),
                        ])
                        ->columnSpanFull(),

                    Tabs\Tab::make('Texto')
                        ->schema([

                            // TiptapEditor::make('texto')
                            //     ->label('Usar o modelo ou o Texto abaixo')
                            //     ->profile('default')
                            //     ->maxContentWidth('5xl'),

                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormfields(),
            )
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                static::getTablefields()
            )
            ->filters([
                SelectFilter::make('curso_id')
                    ->label('Cursos')
                    // ->relationship(
                    //     name: 'cursos',
                    //     titleAttribute: 'titulo',
                    //     modifyQueryUsing: fn(Builder $query) => $query->select('esc_cursos.*')
                    // )
                    ->options(Cursos::pluck('titulo', 'id'))
                    ->searchable(['titulo'])
                    ->preload()
                    ->attribute('curso_id')
                    ->default(request()->get('curso_id'))
                    ->columnSpan(6),

                SelectFilter::make('tipocomunicacao_id')
                    ->label('Tipo')
                    ->relationship('tipo', 'titulo')
                    ->searchable(['titulo'])
                    ->preload()
                    ->columnSpan(2),

                SelectFilter::make('meiocomunicacao_id')
                    ->label('Meio')
                    ->relationship(
                        'meio',
                        'descricao',
                        modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'MEIO_COMUNICACAO'),
                    )
                    ->searchable(['descricao'])
                    ->preload()
                    ->columnSpan(2),

                SelectFilter::make('publico_id')
                    ->label('Público')
                    ->relationship(
                        'publico',
                        'descricao',
                        modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'PUBLICO_ALVO'),
                    )
                    ->searchable(['descricao'])
                    ->preload()
                    ->columnSpan(2),

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

                Action::make('imprimir')
                    ->hiddenLabel()
                    ->size('xl')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record): string => route('imprimir.comunicacao', $record->uuid))
                    ->openUrlInNewTab(),

                Action::make('enviar')
                    ->hiddenLabel()
                    ->size('xl')
                    ->label('Enviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(function (Model $record): void {
                        // PrincipalController::dispararEmailComunicacao($record);

                        Notification::make()
                            ->title('Comunicado Enviado Com Sucesso!')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()

                    ->openUrlInNewTab(),
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
            'index' => Pages\ListComunicacaos::route('/'),
            'create' => Pages\CreateComunicacao::route('/create'),
            'edit' => Pages\EditComunicacao::route('/{record}/edit'),
        ];
    }
}
