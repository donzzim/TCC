<?php

namespace App\Filament\Resources\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Contratacao\ContratacaoVariavelResource\Pages;
use App\Filament\Resources\Contratacao\ContratacaoVariavelResource\RelationManagers;
use App\Models\Contratacao\ContratacaoVariavelModelo;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContratacaoVariavelResource extends Resource
{
    protected static ?string $model = ContratacaoVariavelModelo::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-variable';
    protected static ?string $recordTitleAttribute = 'variavel';
    protected static ?string $modelLabel = 'Variáveis do Documento ';
    protected static ?string $puralModelLabel = 'Variáveis do Documento ';
    protected static ?string $navigationLabel = 'Variáveis do Documento ';
    protected static string | UnitEnum | null $navigationGroup = 'Contratação';
    protected static ?int $navigationSort = 7;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('variavel')
                ->label('Variável')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('descricao')
                ->label('Descrição')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('contexto')
                ->label('Contexto')
                ->required()
                ->maxLength(255),

            // TiptapEditor::make('texto_padrao')
            //     ->label('Texto Padrão')
            //     ->columnSpanFull()
            //     ->extraInputAttributes(['style' => 'min-height: 30rem;']),
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
                Tables\Columns\TextColumn::make('variavel')
                    ->label('Variável')
                    ->searchable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contexto')
                    ->label('Contexto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('texto_padrao')
                    ->label('Texto Padrão')
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
            'index' => Pages\ListContratacaoVariavels::route('/'),
            'create' => Pages\CreateContratacaoVariavel::route('/create'),
            'edit' => Pages\EditContratacaoVariavel::route('/{record}/edit'),
        ];
    }
}
