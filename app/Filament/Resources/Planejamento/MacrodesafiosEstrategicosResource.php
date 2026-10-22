<?php

namespace App\Filament\Resources\Planejamento;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Planejamento\MacrodesafiosEstrategicosResource\Pages;
use App\Filament\Resources\Planejamento\MacrodesafiosEstrategicosResource\RelationManagers;
use App\Models\Planejamento\MacrodesafiosEstrategicos;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MacrodesafiosEstrategicosResource extends Resource
{
    protected static ?string $model = MacrodesafiosEstrategicos::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'macrodesafio';
    protected static ?string $modelLabel = 'Macrodesafio Estratégico ';
    protected static ?string $puralModelLabel = 'Macrodesafio Estratégico ';
    protected static ?string $navigationLabel = 'Macrodesafio Estratégico ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Estratégico';
    protected static ?int $navigationSort = 5;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('macrodesafio')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('setor.setor')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('macrodesafio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('atualizado.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListMacrodesafiosEstrategicos::route('/'),
            'create' => Pages\CreateMacrodesafiosEstrategicos::route('/create'),
            'edit' => Pages\EditMacrodesafiosEstrategicos::route('/{record}/edit'),
        ];
    }
}
