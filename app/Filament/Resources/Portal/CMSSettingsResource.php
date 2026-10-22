<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Portal\CMSSettingsResource\Pages;
use App\Filament\Resources\Portal\CMSSettingsResource\RelationManagers;
use App\Models\Portal\CMSSettings;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSSettingsResource extends Resource
{
    protected static ?string $model = CMSSettings::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $recordTitleAttribute = 'key';
    protected static ?string $modelLabel = 'Configurações ';
    protected static ?string $puralModelLabel = 'Configurações ';
    protected static ?string $navigationLabel = 'Configurações ';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-settings';
    protected static ?int $navigationSort = 8;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('key')
                ->label('Chave (key)')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('value')
                ->label('Valor (value)')
                ->required()
                ->maxLength(255),
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
                Tables\Columns\TextColumn::make('key')
                    ->label('Chave (key)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor (value)')
                    ->searchable(),
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
            'index' => Pages\ListCMSSettings::route('/'),
            'create' => Pages\CreateCMSSettings::route('/create'),
            'edit' => Pages\EditCMSSettings::route('/{record}/edit'),
        ];
    }
}
