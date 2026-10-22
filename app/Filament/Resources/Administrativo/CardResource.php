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
use App\Filament\Resources\Administrativo\CardResource\Pages;
use App\Filament\Resources\CardResource\RelationManagers;
use App\Models\Administrativo\Cards;
use App\Models\Administrativo\Lists;
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
use Illuminate\Support\HtmlString;

class CardResource extends Resource
{
    protected static ?string $model = Cards::class;
    protected static ?string $cluster = FluxoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Card';
    protected static ?string $puralModelLabel = 'Cards';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-square-2-stack';
    protected static ?string $navigationLabel = 'Cards';

    // protected static ?string $navigationParentItem = 'Cadastro';
    // protected static string | UnitEnum | null $navigationGroup = 'Fluxo de Trabalho';
    protected static ?int $navigationSort = 3;
    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('list_id')
                ->options(
                    Lists::all()->pluck('descricao', 'id')
                )
                ->searchable(['descricao'])
                ->label('Lista')
                ->preload()
                ->createOptionForm(
                    ListResource::getFormFields()
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
                Tables\Columns\TextColumn::make('list.descricao')
                    ->label('Lista')
                    ->color(function (Cards $record) {
                        return Color::hex($record->list->cor);
                    })
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->sortable(),
                Tables\Columns\ColorColumn::make('cor')
                    ->label('Cor'),
                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks')
                    ->badge(),
            ])
            ->defaultSort('list_id', 'asc')
            ->defaultGroup('list.descricao')
            ->groupingSettingsHidden()
            ->groupingDirectionSettingHidden()
            ->groups([
                Group::make()
                    ->collapsible(),
            ])
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
            'index' => Pages\ListCards::route('/'),
            'create' => Pages\CreateCard::route('/create'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->descricao;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'list.descricao',
            'list.tipodemanda.descricao',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'List' => $record->list->descricao,
            'Tipo' => $record->list->tipodemanda->descricao,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['list']);
    }
}
