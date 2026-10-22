<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\DocumentacaoResource\Pages;
use App\Filament\Resources\DocumentacaoResource\RelationManagers;
use App\Models\Documentacao;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentacaoResource extends Resource
{
    protected static ?string $model = Documentacao::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'campo';
    protected static ?string $modelLabel = 'Documentação ';
    protected static ?string $puralModelLabel = 'Documentação ';
    protected static ?string $navigationLabel = 'Documentação ';
    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 10;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('rota')
                ->label('Identificador da Rota')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Ex: filament.admin.resources.cursos.index ou global para ajuda geral')
                ->columnSpanFull(),

            Forms\Components\TextInput::make('titulo')
                ->label('Título da Ajuda')
                ->required()
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('conteudo')
                ->label('Conteúdo')
                ->required()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('ativo')
                ->default(true),
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
                Tables\Columns\TextColumn::make('rota')
                    ->label('Rota')
                    ->searchable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rota')
                    ->label('Rota')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('ativo')
                    ->label('Ativo')
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
            'index' => Pages\ListDocumentacaos::route('/'),
            'create' => Pages\CreateDocumentacao::route('/create'),
            'edit' => Pages\EditDocumentacao::route('/{record}/edit'),
        ];
    }
}
