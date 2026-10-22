<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Portal\CMSMenuItemsResource\Pages;
use App\Filament\Resources\Portal\CMSMenuItemsResource\RelationManagers;
use App\Models\Portal\CMSMenuItems;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSMenuItemsResource extends Resource
{
    protected static ?string $model = CMSMenuItems::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $recordTitleAttribute = 'label';
    protected static ?string $modelLabel = 'Menu Itens ';
    protected static ?string $puralModelLabel = 'Menu Itens ';
    protected static ?string $navigationLabel = 'Menu Itens ';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-menu-items';
    protected static ?int $navigationSort = 3;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('label')
                ->required(),
            Forms\Components\TextInput::make('url')
                ->required(),
            Forms\Components\TextInput::make('order')
                ->numeric()
                ->default(0),
            Forms\Components\Select::make('parent_id')
                ->label('Item Pai')
                ->options(
                    CMSMenuItems::query()
                        ->whereNull('parent_id')
                        ->orderBy('order')
                        ->pluck('label', 'id')
                )
                ->searchable()
                ->nullable()
                ->helperText('Selecione um menu pai para criar sub‑item'),
            Forms\Components\Toggle::make('open_in_new_tab'),
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
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.label')
                    ->label('Pai'),
                Tables\Columns\TextColumn::make('url'),
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('open_in_new_tab')
                    ->boolean(),
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
            'index' => Pages\ListCMSMenuItems::route('/'),
            'create' => Pages\CreateCMSMenuItems::route('/create'),
            'edit' => Pages\EditCMSMenuItems::route('/{record}/edit'),
        ];
    }
}
