<?php

namespace App\Filament\Resources\Administrativo;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Administrativo\FluxoCluster;
use App\Filament\Resources\Administrativo\ListResource\Pages;
use App\Filament\Resources\ListResource\RelationManagers;
use App\Models\Administrativo\Lists;
use App\Models\Administrativo\TipoDemandas;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListResource extends Resource
{
    protected static ?string $model = Lists::class;
    protected static ?string $cluster = FluxoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'List';
    protected static ?string $puralModelLabel = 'Lists';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Lists';

    // protected static ?string $navigationParentItem = 'Cadastro';
    // protected static string | UnitEnum | null $navigationGroup = 'Fluxo de Trabalho';
    protected static ?int $navigationSort = 2;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('tipodemanda_id')
                ->options(
                    TipoDemandas::all()->pluck('descricao', 'id')
                )
                ->searchable(['descricao'])
                ->label('Tipo Demanda')
                ->preload()
                // ->createOptionForm(
                //     TipoDemandaResource::getFormFields()
                // )
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
                Tables\Columns\TextColumn::make('tipodemanda.descricao')
                    ->label('Tipo Demanda')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('cor')
                    ->label('Cor'),
                Tables\Columns\TextColumn::make('cards_count')
                    ->label('Cards')
                    ->counts('cards')
                    ->badge(),
            ])
            ->reorderable('ordem')
            ->defaultSort('ordem', 'asc')
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
            'index' => Pages\ListLists::route('/'),
            'create' => Pages\CreateList::route('/create'),
            'edit' => Pages\EditList::route('/{record}/edit'),
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
            'tipodemanda.descricao',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Tipo' => $record->tipodemanda->descricao,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['tipodemanda']);
    }
}
