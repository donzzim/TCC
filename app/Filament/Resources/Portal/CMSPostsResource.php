<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Resources\Portal\CMSPostsResource\Pages;
use App\Filament\Resources\Portal\CMSPostsResource\RelationManagers;
use App\Models\Portal\CMSPosts;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CMSPostsResource extends Resource
{
    protected static ?string $model = CMSPosts::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $modelLabel = 'Posts ';
    protected static ?string $puralModelLabel = 'Posts ';
    protected static ?string $navigationLabel = 'Posts ';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-posts';
    protected static ?int $navigationSort = 6;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label('Título')
                ->required()
                ->reactive()
                ->live(onBlur: true)
                ->afterStateUpdated(fn(string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->unique(CMSPosts::class, 'slug', ignoreRecord: true)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('excerpt')
                ->label('Resumo')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('content')
                ->label('Conteúdo')
                ->required()
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('image_path')
                ->label('Imagem de Destaque')
                ->image()
                ->directory('posts'),

            Forms\Components\Toggle::make('is_published')
                ->label('Publicado'),
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
                Tables\Columns\ImageColumn::make('image_path')->label('Imagem'),
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Publicado'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Criado em')->sortable(),
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
            'index' => Pages\ListCMSPosts::route('/'),
            'create' => Pages\CreateCMSPosts::route('/create'),
            'edit' => Pages\EditCMSPosts::route('/{record}/edit'),
        ];
    }
}
