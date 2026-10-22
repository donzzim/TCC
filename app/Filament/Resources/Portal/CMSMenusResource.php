<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Portal\CMSMenusResource\Pages;
use App\Filament\Resources\Portal\CMSMenusResource\RelationManagers;
use App\Models\Portal\CMSMenus;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSMenusResource extends Resource
{
    protected static ?string $model = CMSMenus::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bars-3';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Menus ';
    protected static ?string $puralModelLabel = 'Menus ';
    protected static ?string $navigationLabel = 'Menus ';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-menus';
    protected static ?int $navigationSort = 2;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('name')->required(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Nº de Itens'),
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
            RelationManagers\CMSItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCMSMenuses::route('/'),
            'create' => Pages\CreateCMSMenus::route('/create'),
            'edit' => Pages\EditCMSMenus::route('/{record}/edit'),
        ];
    }
}
