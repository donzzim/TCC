<?php

namespace App\Filament\Resources\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Group;
use App\Filament\Resources\Administrativo\TarefaResource\Pages;
use App\Filament\Resources\TarefaResource\RelationManagers;
use App\Models\Administrativo\Tarefas;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TarefaResource extends Resource
{
    protected static ?string $model = Tarefas::class;

    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Tarefa';
    protected static ?string $puralModelLabel = 'Tarefas';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Tarefas';

    // protected static ?string $navigationParentItem = 'Demandas';
    protected static string | UnitEnum | null $navigationGroup = 'Administrativo';
    protected static ?int $navigationSort = 3;
    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('demanda_id')
                ->relationship(name: 'demanda', titleAttribute: 'titulo')
                ->searchable(['titulo'])
                ->label('Demanda')
                ->preload()
                ->createOptionForm(
                    DemandaResource::getFormFields()
                )
                ->columnSpan(2),
            Forms\Components\Select::make('card_id')
                ->relationship(name: 'card', titleAttribute: 'descricao')
                ->searchable(['descricao'])
                ->label('Cards')
                ->preload()
                ->createOptionForm(
                    CardResource::getFormFields()
                )
                ->columnSpan(2),
            Forms\Components\TextInput::make('descricao')
                ->label('Descrição')
                ->required()
                ->maxLength(255),
            Forms\Components\ColorPicker::make('cor'),
            Forms\Components\TextInput::make('ordem')
                ->label('Ordem')
                ->required()
                ->integer()
                ->default(0),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormfields(),
            );
    }

    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('demanda.titulo')
                ->label('Demanda')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('card.descricao')
                ->label('Cards')
                ->color(function (Tarefas $record) {
                    return Color::hex($record->card->cor);
                })
                ->badge()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('descricao')
                ->label('Descrição')
                ->searchable()
                ->sortable(),
            Tables\Columns\ColorColumn::make('cor')
                ->label('Cor'),
            Tables\Columns\TextColumn::make('data_concluida')
                ->label('Concluída em')
                ->dateTime('d/m/Y H:i')
                ->searchable()
                ->sortable(),
        ];
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                static::getTablefields(),
            )
            ->defaultGroup('demanda.titulo')
            ->groupingSettingsHidden()
            ->groupingDirectionSettingHidden()
            ->groups([
                Group::make()
                    ->collapsible(),
            ])
            ->filters([
                //
            ])
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
            'index' => Pages\ListTarefas::route('/'),
            'create' => Pages\CreateTarefa::route('/create'),
            'edit' => Pages\EditTarefa::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->descricao;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'descricao',
            'card.descricao',
            'card.list.descricao',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Tipo' => $record->card->list->tipodemanda->descricao,
            'List' => $record->card->list->descricao,
            'Card' => $record->card->descricao,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['card']);
    }
}
