<?php

namespace App\Filament\Resources\Portal\CMSPagesResource\RelationManagers;

use App\Models\Portal\CMSPageArticles;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CMSPageArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    protected static ?string $title = 'Publicações';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->template === 'revista';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Dados da publicação')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('author')
                            ->label('Autor')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('publication_type')
                            ->label('Tipo')
                            ->options([
                                'publicacao' => 'Publicação',
                                'artigo' => 'Artigo',
                                'monografia' => 'Monografia',
                                'revista' => 'Revista',
                                'outro' => 'Outro',
                            ])
                            ->default('publicacao')
                            ->required(),
                        Forms\Components\TextInput::make('issn')
                            ->label('ISSN')
                            ->placeholder('Ex.: 1234-5678')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('doi')
                            ->label('DOI')
                            ->placeholder('Ex.: 10.1234/revista.v1n1.2026')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('volume')
                            ->label('Volume')
                            ->maxLength(255),
                        Forms\Components\Select::make('month')
                            ->label('Mês')
                            ->options([
                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
                            ]),
                        Forms\Components\TextInput::make('year')
                            ->label('Ano')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(2100),
                        Forms\Components\TextInput::make('pages')
                            ->label('Páginas')
                            ->placeholder('Ex.: 12-28')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordem')
                            ->numeric()
                            ->default(0),
                        Forms\Components\DatePicker::make('published_at')
                            ->label('Data de publicação')
                            ->native(false),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Arquivo')
                    ->schema([
                        Forms\Components\FileUpload::make('pdf_path')
                            ->label('PDF')
                            ->disk('revista')
                            ->directory('')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            ->openable()
                            ->downloadable()
                            ->required(),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Publicado')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->wrap()->sortable(),
                Tables\Columns\TextColumn::make('author')->label('Autor')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('publication_type_label')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('publication_type', $direction)),
                Tables\Columns\TextColumn::make('issn')->label('ISSN')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('doi')->label('DOI')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('volume')->label('Volume')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('month_name')
                    ->label('Mês')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('month', $direction)),
                Tables\Columns\TextColumn::make('year')->label('Ano')->sortable(),
                Tables\Columns\TextColumn::make('pages')->label('Páginas')->toggleable(),
                Tables\Columns\IconColumn::make('is_published')->label('Publicado')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordem')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('year', 'desc')
            ->headerActions([
                CreateAction::make()->label('Nova publicação'),
            ])
            ->recordActions([
                Action::make('abrir_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CMSPageArticles $record): ?string => $record->pdf_url)
                    ->openUrlInNewTab()
                    ->hidden(fn (CMSPageArticles $record): bool => blank($record->pdf_path)),
                EditAction::make()->hiddenLabel(),
                DeleteAction::make()->hiddenLabel(),
            ])
            ->toolBarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped();
    }
}
