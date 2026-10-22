<?php

namespace App\Filament\Resources\Planejamento;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Planejamento\IniciativasEstrategicasResource\Pages;
use App\Filament\Resources\Planejamento\IniciativasEstrategicasResource\RelationManagers;
use App\Models\Planejamento\IniciativasEstrategicas;
use App\Models\Planejamento\ObjetivosEstrategicos;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IniciativasEstrategicasResource extends Resource
{
    protected static ?string $model = IniciativasEstrategicas::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'iniciativa';
    protected static ?string $modelLabel = 'Iniciativas Estratégicas ';
    protected static ?string $puralModelLabel = 'Iniciativas Estratégicas ';
    protected static ?string $navigationLabel = 'Iniciativas Estratégicas ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Estratégico';
    protected static ?int $navigationSort = 7;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('objetivo_id')
                ->options(
                    ObjetivosEstrategicos::orderBy('objetivo', 'asc')->pluck('objetivo', 'id')
                )
                ->preload()
                ->createOptionForm(
                    PlanejamentoEstrategicoResource::getFormFields()
                )
                ->required()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('codigo')
                ->maxLength(255)
                ->columnSpan(1),

            Forms\Components\TextInput::make('iniciativa')
                ->required()
                ->maxLength(255)
                ->columnSpan(3),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $form
            ->columns(4)
            ->schema(static::getFormFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('objetivo.objetivo')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('iniciativa')
                    ->searchable(),
                Tables\Columns\TextColumn::make('setor.setor')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('atualizado.name')
                    ->wrap()
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
            'index' => Pages\ListIniciativasEstrategicas::route('/'),
            'create' => Pages\CreateIniciativasEstrategicas::route('/create'),
            'edit' => Pages\EditIniciativasEstrategicas::route('/{record}/edit'),
        ];
    }
}
