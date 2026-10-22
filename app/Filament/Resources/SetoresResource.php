<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\SetoresResource\Pages;
use App\Filament\Resources\SetoresResource\RelationManagers;
use App\Models\Setores;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SetoresResource extends Resource
{
    protected static ?string $model = Setores::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $recordTitleAttribute = 'setor';
    protected static ?string $modelLabel = 'Setores ';
    protected static ?string $puralModelLabel = 'Setores ';
    protected static ?string $navigationLabel = 'Setores ';
    protected static string | UnitEnum | null $navigationGroup = 'Controle de Acesso';
    protected static ?int $navigationSort = 3;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('unidadeorganizacional')
                ->label('Unidade')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('setor')
                ->label('Setor')
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

                Tables\Columns\TextColumn::make('setor')
                    ->label('Setor')
                    ->searchable()
                    ->sortable(),
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
    public static function getNavigationBadge(): ?string
    {
        $count = number_format(static::getModel()::count(), 0, ',', '.');
        return $count;
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
            'index' => Pages\ListSetores::route('/'),
            'create' => Pages\CreateSetores::route('/create'),
            'edit' => Pages\EditSetores::route('/{record}/edit'),
        ];
    }
}
