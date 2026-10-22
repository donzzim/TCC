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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Resources\Portal\CMSSectionsResource\Pages;
use App\Filament\Resources\Portal\CMSSectionsResource\RelationManagers;
use App\Models\Portal\CMSSections;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSSectionsResource extends Resource
{
    protected static ?string $model = CMSSections::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $recordTitleAttribute = 'type';
    protected static ?string $modelLabel = 'Seções ';
    protected static ?string $puralModelLabel = 'Seções ';
    protected static ?string $navigationLabel = 'Seções ';
    protected static string | UnitEnum | null $navigationGroup = 'Portal';
    protected static ?string $slug = 'cms-sections';
    protected static ?int $navigationSort = 4;

    public static function getSectionSchema(string $type = null): array
    {
        return match ($type) {
            'hero' => [
                Forms\Components\TextInput::make('content.title')
                    ->label('Título Principal'),
                Forms\Components\Textarea::make('content.text')
                    ->label('Texto de Apoio'),
                Forms\Components\Repeater::make('content.buttons')
                    ->label('Botões')
                    ->schema([
                        Forms\Components\TextInput::make('link')
                            ->prefix('http://')
                            ->label('Link'),
                        Forms\Components\TextInput::make('label')
                            ->label('Rótulo'),
                        Forms\Components\TextInput::make('class')
                            ->label('Classe CSS'),
                    ])->columns(3),
            ],
            'stats' => [
                Forms\Components\Repeater::make('content.stats')
                    ->label('Estatísticas')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->label('Número'),
                        Forms\Components\TextInput::make('label')
                            ->label('Rótulo'),
                    ])->columns(2),
            ],
            'about' => [
                Forms\Components\TextInput::make('content.title')
                    ->label('Título da Seção'),
                Forms\Components\Textarea::make('content.text1')
                    ->label('Primeiro Parágrafo'),
                Forms\Components\Textarea::make('content.text2')
                    ->label('Segundo Parágrafo'),
                Forms\Components\FileUpload::make('content.image')
                    ->label('Imagem de Destaque'),
            ],
            'courses' => [
                Forms\Components\TextInput::make('content.title')
                    ->label('Título da Seção'),
                Forms\Components\TextInput::make('content.subtitle')
                    ->label('Subtítulo'),
                Forms\Components\Repeater::make('content.items')
                    ->label('Itens do Curso')
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('Título do Curso'),
                        Forms\Components\TextInput::make('description')->label('Descrição'),
                    ]),
            ],
            default => [],
        };
    }

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('type')
                ->label('Tipo de Seção')
                ->options([
                    'hero' => 'Hero (Banner Principal)',
                    'stats' => 'Stats (Estatísticas)',
                    'about' => 'About (Sobre)',
                    'courses' => 'Courses (Cursos)',
                ])
                ->required()
                ->reactive()
                ->live()
                ->afterStateUpdated(fn(Set $set) => $set('content', [])),

            Section::make('Conteúdo da Seção')
                ->description('Preencha os campos conforme o tipo de seção selecionado acima.')
                ->visible(fn(Get $get) => !empty($get('type')))
                ->schema(fn(Get $get) => static::getSectionSchema($get('type'))),
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->searchable()
                    ->extraAttributes([
                        'class' => 'text-lg',
                    ]),
                Tables\Columns\TextColumn::make('content')
                    ->label('Resumo do Conteúdo')
                    ->limit(70)
                    ->tooltip('Clique em editar para ver o conteúdo completo')
                    ->formatStateUsing(function ($state, $record): string {
                        if (empty($state)) {
                            return 'Vazio';
                        }
                        return match ($record->type) {
                            'hero' => $state ?? 'Sem título',
                            'about' => $state ?? 'Sem título',
                            'courses' => $state ?? 'Sem título',
                            'stats' => collect($state ?? [])->pluck('label')->implode(', '),
                            default => 'Conteúdo não formatado',
                        };
                    }),
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
            'index' => Pages\ListCMSSections::route('/'),
            'create' => Pages\CreateCMSSections::route('/create'),
            'edit' => Pages\EditCMSSections::route('/{record}/edit'),
        ];
    }
}
