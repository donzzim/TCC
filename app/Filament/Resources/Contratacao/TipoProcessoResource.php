<?php

namespace App\Filament\Resources\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Contratacao\ProcessoCluster;
use App\Filament\Resources\Contratacao\TipoProcessoResource\Pages;
use App\Filament\Resources\Contratacao\TipoProcessoResource\RelationManagers;
use App\Models\Contratacao\TipoProcesso;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TipoProcessoResource extends Resource
{
    protected static ?string $model = TipoProcesso::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = ProcessoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Tipo de Processo ';
    protected static ?string $puralModelLabel = 'Tipo de Processo ';
    protected static ?string $navigationLabel = 'Tipo de Processo ';
    // protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 7;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('codigo')
                ->label('Código')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('descricao')
                ->label('Descrição')
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
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
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
            'index' => Pages\ListTipoProcessos::route('/'),
            'create' => Pages\CreateTipoProcesso::route('/create'),
            'edit' => Pages\EditTipoProcesso::route('/{record}/edit'),
        ];
    }
}
