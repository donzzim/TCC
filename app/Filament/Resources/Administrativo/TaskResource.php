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
use App\Filament\Clusters\Administrativo\FluxoCluster;
use App\Filament\Resources\Administrativo\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Administrativo\Cards;
use App\Models\Administrativo\Tasks;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;

use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Tasks::class;
    protected static ?string $cluster = FluxoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Task';
    protected static ?string $puralModelLabel = 'Tasks';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-square-3-stack-3d';
    protected static ?string $navigationLabel = 'Tasks';

    // protected static ?string $navigationParentItem = 'Cadastro';
    // protected static string | UnitEnum | null $navigationGroup = 'Fluxo de Trabalho';
    protected static ?int $navigationSort = 4;
    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('card_id')
                ->options(
                    Cards::all()->pluck('descricao', 'id')
                )
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('card.descricao')
                    ->label('Cards')
                    ->color(function (Tasks $record) {
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
            ])
            ->defaultGroup('card.descricao')
            ->groupingSettingsHidden()
            ->groupingDirectionSettingHidden()
            ->groups([
                Group::make()
                    ->collapsible(),
            ])
            ->defaultSort('card_id', 'asc')
            ->reorderable('ordem')
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
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
