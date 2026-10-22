<?php

namespace App\Filament\Resources\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Contratacao\OrcamentoCluster;
use App\Filament\Resources\Contratacao\PlanoOrcamentarioResource\Pages;
use App\Filament\Resources\PlanoOrcamentarioResource\RelationManagers;
use App\Models\Contratacao\PlanoOrcamentario;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanoOrcamentarioResource extends Resource
{
    protected static ?string $model = PlanoOrcamentario::class;
    protected static ?string $cluster = OrcamentoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Plano Orçamentário';
    protected static ?string $puralModelLabel = 'Plano Orçamentário ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bars-2';
    protected static ?string $navigationLabel = 'Plano Orçamentário';
    // protected static ?string $navigationParentItem = 'Orçamento';
    // protected static string | UnitEnum | null $navigationGroup = 'Orçamento';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->unique()
                    ->maxLength(255),
                Forms\Components\TextInput::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255),
            ]);
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
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),
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
            'index' => Pages\ListPlanoOrcamentarios::route('/'),
            'create' => Pages\CreatePlanoOrcamentario::route('/create'),
            'edit' => Pages\EditPlanoOrcamentario::route('/{record}/edit'),
        ];
    }
}
