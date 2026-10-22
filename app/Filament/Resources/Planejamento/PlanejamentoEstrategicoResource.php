<?php

namespace App\Filament\Resources\Planejamento;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Planejamento\PlanejamentoEstrategicoResource\Pages;
use App\Filament\Resources\Planejamento\PlanejamentoEstrategicoResource\RelationManagers;
use App\Models\Planejamento\PlanejamentoEstrategico;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanejamentoEstrategicoResource extends Resource
{
    protected static ?string $model = PlanejamentoEstrategico::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'missao';
    protected static ?string $modelLabel = 'Planejamento Estratégico ';
    protected static ?string $puralModelLabel = 'Planejamento Estratégico ';
    protected static ?string $navigationLabel = 'Planejamento Estratégico ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Estratégico';
    protected static ?int $navigationSort = 2;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('periodo')
                ->required()
                ->maxLength(9)
                ->placeholder('AAAA/AAAA')
                ->columnSpan(1)
                ->mask('9999/9999'),
            Forms\Components\TextInput::make('missao')
                ->label('Missão')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('visao')
                ->label('Visão')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('valores')
                ->label('Valores')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('setor.setor')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('periodo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('missao')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('visao')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('valores')
                    ->searchable()
                    ->wrap(),
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
            'index' => Pages\ListPlanejamentoEstrategicos::route('/'),
            'create' => Pages\CreatePlanejamentoEstrategico::route('/create'),
            'edit' => Pages\EditPlanejamentoEstrategico::route('/{record}/edit'),
        ];
    }
}
