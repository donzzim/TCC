<?php

namespace App\Filament\Resources\Planejamento;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Planejamento\PerspectivasOrganizacionaisResource\Pages;
use App\Filament\Resources\Planejamento\PerspectivasOrganizacionaisResource\RelationManagers;
use App\Models\Planejamento\PerspectivasOrganizacionais;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PerspectivasOrganizacionaisResource extends Resource
{
    protected static ?string $model = PerspectivasOrganizacionais::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'Perspectivas Organizacionais';
    protected static ?string $modelLabel = 'Perspectivas Organizacionais ';
    protected static ?string $puralModelLabel = 'Perspectivas Organizacionais ';
    protected static ?string $navigationLabel = 'Perspectivas Organizacionais ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Estratégico';
    protected static ?int $navigationSort = 3;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('perspectiva')
                ->required()
                ->maxLength(255)
                ->columnSpan(4),
            Forms\Components\ColorPicker::make('cor')
                ->label('Cor')
                ->columnSpan(1),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $form
            ->columns(6)
            ->schema(static::getFormFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('setor.setor')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('perspectiva')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('atualizado.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Descricao')
                    ->label('Descrição')
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
            'index' => Pages\ListPerspectivasOrganizacionais::route('/'),
            'create' => Pages\CreatePerspectivasOrganizacionais::route('/create'),
            'edit' => Pages\EditPerspectivasOrganizacionais::route('/{record}/edit'),
        ];
    }
}
