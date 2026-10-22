<?php

namespace App\Filament\Resources\Portal;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Resources\Portal\CMSPagesResource\Pages;
use App\Filament\Resources\Portal\CMSPagesResource\RelationManagers;
use App\Models\Portal\CMSPages;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CMSPagesResource extends Resource
{
    protected static ?string $model = CMSPages::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document';
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $modelLabel = 'Página';
    protected static ?string $pluralModelLabel = 'Páginas';
    protected static ?string $navigationLabel = 'Páginas';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-pages';
    protected static ?int $navigationSort = 5;

    public static function getFormfields(): array
    {
        return [
            Group::make()
                ->schema([
                    Section::make('Conteúdo Principal')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Título')
                                ->required()
                                ->reactive()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true),

                            Forms\Components\RichEditor::make('content')
                                ->label('Conteúdo')
                                ->columnSpanFull(),
                        ])->columns(2),
                ])->columnSpan(2),

            Group::make()
                ->schema([
                    Section::make('SEO')
                        ->schema([
                            Forms\Components\TextInput::make('seo_title')->label('Título SEO'),
                            Forms\Components\Textarea::make('seo_description')->label('Descrição SEO'),
                            Forms\Components\TagsInput::make('seo_keywords')->label('Palavras-chave SEO'),
                        ]),
                    Section::make('Atributos')
                        ->schema([
                            Forms\Components\Select::make('template')
                                ->label('Modelo de Página')
                                ->required()
                                ->default('default')
                                ->options([
                                    'default' => 'Padrão',
                                    'full-width' => 'Largura Total',
                                    'sidebar' => 'Com Barra Lateral',
                                    'revista' => 'Revista com Publicações',
                                ]),
                        ])
                ])->columnSpan(1),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())
            ->columns(3)->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
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
            RelationManagers\CMSPageArticlesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCMSPages::route('/'),
            'create' => Pages\CreateCMSPages::route('/create'),
            'edit' => Pages\EditCMSPages::route('/{record}/edit'),
        ];
    }
}
