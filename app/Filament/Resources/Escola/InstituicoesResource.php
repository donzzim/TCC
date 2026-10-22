<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Escola\InstituicoesResource\Pages;
use App\Filament\Resources\InstituicoesResource\RelationManagers;
use App\Models\Escola\Instituicoes;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstituicoesResource extends Resource
{
    protected static ?string $model = Instituicoes::class;
    protected static ?string $recordTitleAttribute = 'nome';
    protected static ?string $modelLabel = 'Instituições';
    protected static ?string $puralModelLabel = 'Instituições';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Instituições';
    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 5;

    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->columnSpan(3),

            Forms\Components\TextInput::make('link')
                ->label('Link')
                ->columnSpan(2),

            Forms\Components\FileUpload::make('logo')
                ->label('Logo')
                ->columnSpan(1),

            Forms\Components\RichEditor::make('descricao')
                ->label('Descrição')
                ->columnSpanFull(),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormFields()
            )
            ->columns(4);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('link')
                    ->label('Link')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                ViewAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-eye')
                    ->size('xl')
                    ->tooltip('visualizar')
                    ->modal(),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListInstituicoes::route('/'),
            'create' => Pages\CreateInstituicoes::route('/create'),
            'edit' => Pages\EditInstituicoes::route('/{record}/edit'),
        ];
    }
}
