<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Cadastro\TabelasCluster;
use App\Filament\Resources\Escola\TipoAtividadesResource\Pages;
use App\Filament\Resources\TipoAtividadesResource\RelationManagers;
use App\Models\Escola\TipoAtividade;
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

class TipoAtividadesResource extends Resource
{
    protected static ?string $model = TipoAtividade::class;
    protected static ?string $cluster = TabelasCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Tipo de Atividade';
    protected static ?string $puralModelLabel = 'Tipo de Atividades';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Tipo de Atividades';

    // protected static ?string $navigationParentItem = 'Cadastro';
    // protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 5;

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
            'index' => Pages\ListTipoAtividades::route('/'),
            'create' => Pages\CreateTipoAtividades::route('/create'),
            'edit' => Pages\EditTipoAtividades::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->descricao;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'codigo',
            'descricao',
        ];
    }
}
