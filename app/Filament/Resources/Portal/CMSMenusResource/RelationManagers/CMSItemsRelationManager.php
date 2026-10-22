<?php

namespace App\Filament\Resources\Portal\CMSMenusResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkActionGroup;
use App\Models\Portal\CMSPages;
use App\Models\Portal\CMSPosts;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CMSItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $recordTitleAttribute = 'label';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('label')
                    ->label('Rótulo (Texto do link)')
                    ->required(),

                Forms\Components\Select::make('link_type')
                    ->label('Tipo de Link')
                    ->options([
                        'manual' => 'URL Manual',
                        'page' => 'Página',
                        'post' => 'Post',
                    ])
                    ->default('manual')
                    ->reactive()
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('url', '')),

                Forms\Components\Select::make('page_id')
                    ->label('Selecione uma Página')
                    ->options(CMSPages::all()->pluck('title', 'id'))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $page = CMSPages::find($state);
                            $set('url', '/' . ltrim($page->slug, '/'));
                        }
                    })
                    // Só aparece se o tipo de link for 'Página'
                    ->visible(fn(Get $get) => $get('link_type') === 'page'),

                Forms\Components\Select::make('post_id')
                    ->label('Selecione um Post')
                    ->options(CMSPosts::all()->pluck('title', 'id'))
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state) {
                            $post = CMSPosts::find($state);
                            $set('url', '/noticias/' . ltrim($post->slug, '/'));
                        }
                    })
                    ->visible(fn(Get $get) => $get('link_type') === 'post'),

                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->required()
                    // Fica "apenas leitura" se a URL estiver sendo gerada por uma Página ou Post
                    ->readOnly(fn(Get $get) => in_array($get('link_type'), ['page', 'post']))
                    ->helperText(function (Get $get) {
                        return match ($get('link_type')) {
                            'page', 'post' => 'A URL é gerada automaticamente.',
                            default => 'Insira um caminho relativo (ex: /contato) ou uma URL completa (ex: https://google.com).',
                        };
                    })
                    ->columnSpanFull(),

                Forms\Components\Select::make('parent_id')
                    ->label('Item Pai')
                    ->options(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()
                            ->items()
                            ->whereNull('parent_id')
                            ->pluck('label', 'id');
                    })
                    ->searchable()
                    ->nullable(),
                Forms\Components\Toggle::make('open_in_new_tab')
                    ->label('Abrir em nova aba?'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('menu_id')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Rótulo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.label')
                    ->label('Pai')
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('open_in_new_tab')
                    ->boolean()
                    ->label('Nova Aba')
                    ->sortable(),
            ])
            ->reorderable('order')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ReplicateAction::make()
                    ->hiddenLabel(),
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
            ]);
    }
}
