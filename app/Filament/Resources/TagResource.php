<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Clusters\Cadastro\TabelasCluster;
use App\Filament\Resources\TagResource\Pages;
use App\Filament\Resources\TagResource\RelationManagers;
use App\Models\Tags;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TagResource extends Resource
{
    protected static ?string $model = Tags::class;
    protected static ?string $cluster = TabelasCluster::class;
    // protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Tag';
    protected static ?string $puralModelLabel = 'Tags';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Tags';
    // protected static ?string $navigationParentItem = 'Cadastro';
    // protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 1;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('tag_id')
                ->relationship('tag', 'descricao')
                ->searchable()
                ->preload()
                ->label('Tag Pai'),

            Forms\Components\TextInput::make('descricao')
                ->label('Descrição')
                ->required()
                ->maxLength(255),

            Forms\Components\ColorPicker::make('cor'),
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
                Tables\Columns\TextColumn::make('tag.descricao')
                    ->label('Tag Pai'),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição'),

                Tables\Columns\ColorColumn::make('cor')
                    ->label('Cor'),
            ])
            // ->defaultGroup('tag.descricao')
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
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
