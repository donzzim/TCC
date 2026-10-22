<?php

namespace App\Filament\Resources\Planejamento;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource\Pages;
use App\Filament\Resources\Planejamento\ObjetivosEstrategicosResource\RelationManagers;
use App\Models\Planejamento\MacrodesafiosEstrategicos;
use App\Models\Planejamento\ObjetivosEstrategicos;
use App\Models\Planejamento\PerspectivasOrganizacionais;
use App\Models\Planejamento\PlanejamentoEstrategico;
use App\Models\Planejamento\TemasEstrategicos;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ObjetivosEstrategicosResource extends Resource
{
    protected static ?string $model = ObjetivosEstrategicos::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'objetivo';
    protected static ?string $modelLabel = 'Objetivo Estratégico ';
    protected static ?string $puralModelLabel = 'Objetivo Estratégico ';
    protected static ?string $navigationLabel = 'Objetivo Estratégico ';
    protected static string | UnitEnum | null $navigationGroup = 'Planejamento Estratégico';
    protected static ?int $navigationSort = 6;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('planejamento_id')
                ->options(
                    PlanejamentoEstrategico::orderBy('periodo', 'desc')->pluck('periodo', 'id')
                )
                ->preload()
                ->createOptionForm(
                    PlanejamentoEstrategicoResource::getFormFields()
                )
                ->required()
                ->columnSpan(3),

            Forms\Components\Select::make('perspectiva_id')
                ->options(
                    PerspectivasOrganizacionais::orderBy('perspectiva', 'asc')->pluck('perspectiva', 'id')
                )
                ->preload()
                ->createOptionForm(
                    PerspectivasOrganizacionaisResource::getFormFields()
                )
                ->columnSpan(3),

            Forms\Components\Select::make('tema_id')
                ->options(
                    TemasEstrategicos::orderBy('tema', 'asc')->pluck('tema', 'id')
                )
                ->preload()
                ->createOptionForm(
                    TemasEstrategicosResource::getFormFields()
                )
                ->columnSpan(3),

            Forms\Components\Select::make('macrodesafio_id')
                ->options(
                    MacrodesafiosEstrategicos::orderBy('macrodesafio', 'asc')->pluck('macrodesafio', 'id')
                )
                ->preload()
                ->createOptionForm(
                    MacrodesafiosEstrategicosResource::getFormFields()
                )
                ->columnSpan(3),

            Forms\Components\TextInput::make('objetivo')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('texto_complementar')
                ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('planejamento.periodo')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('perspectiva.perspectiva')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tema.tema')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('macrodesafio.macrodesafio')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('objetivo')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('setor.setor')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('atualizado.name')
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
            'index' => Pages\ListObjetivosEstrategicos::route('/'),
            'create' => Pages\CreateObjetivosEstrategicos::route('/create'),
            'edit' => Pages\EditObjetivosEstrategicos::route('/{record}/edit'),
        ];
    }
}
