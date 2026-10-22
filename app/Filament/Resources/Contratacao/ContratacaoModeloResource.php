<?php

namespace App\Filament\Resources\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Contratacao\ContratacaoModeloResource\Pages;
use App\Filament\Resources\Contratacao\ContratacaoModeloResource\RelationManagers;
use App\Models\Contratacao\ContratacaoModelo;
use App\Models\Contratacao\ContratacaoModeloDocumento;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContratacaoModeloResource extends Resource
{
    protected static ?string $model = ContratacaoModeloDocumento::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $recordTitleAttribute = 'nome';
    protected static ?string $modelLabel = 'Modelos de Documentos ';
    protected static ?string $puralModelLabel = 'Modelos de Documentos ';
    protected static ?string $navigationLabel = 'Modelos de Documentos ';
    protected static string | UnitEnum | null $navigationGroup = 'Contratação';
    protected static ?int $navigationSort = 8;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('contexto')
                ->label('Contexto')
                ->required()
                ->maxLength(255),

            // TiptapEditor::make('conteudo')
            //     ->label('Modelo')
            //     ->columnSpanFull()
            //     ->extraInputAttributes(['style' => 'min-height: 30rem;']),
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(static::getFormFields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contexto')
                    ->label('Contexto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('conteudo')
                    ->label('Texto Padrão')
                    ->searchable()
                    // ->html()
                    // ->state(function ($state) {
                    //     return $state;
                    // })
                    ->limit(256),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->hiddenLabel(),
                ViewAction::make()
                    ->hiddenLabel(),
                DeleteAction::make()
                    ->hiddenLabel(),
            ])
            ->toolbarActions([
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
            'index' => Pages\ListContratacaoModelos::route('/'),
            'create' => Pages\CreateContratacaoModelo::route('/create'),
            'edit' => Pages\EditContratacaoModelo::route('/{record}/edit'),
        ];
    }
}
