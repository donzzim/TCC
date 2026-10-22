<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Escola\ComunicacaoTipoResource\Pages;
use App\Filament\Resources\ComunicacaoTipoResource\RelationManagers;
use App\Models\Escola\ComunicacaoTipo;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ComunicacaoTipoResource extends Resource
{
    protected static ?string $model = ComunicacaoTipo::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Comunicação Tipo ';
    protected static ?string $puralModelLabel = 'Comunicação Tipo ';
    protected static ?string $navigationLabel = 'Comunicação Tipo';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 8;

    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('titulo')
                ->label('Título')
                ->wrap()
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('texto')
                ->label('Texto')
                ->searchable()
                ->html()
                ->sortable(),
        ];
    }
    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('titulo')
                ->label('Título')
                ->required()
                ->columnSpan(6),

            // Forms\Components\RichEditor::make('texto')
            //     ->label('Texto')
            //     ->columnSpanFull(),
            // TiptapEditor::make('texto')
            //     ->label('Texto Modelo')
            //     ->profile('default')
            //     ->maxContentWidth('5xl')
            //     ->extraInputAttributes(['style' => 'min-height: 20rem;'])
            //     ->columnSpanFull(),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormfields(),
            )
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                static::getTablefields()
            )
            ->filters([
                //
            ])
            ->selectable()
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
            'index' => Pages\ListComunicacaoTipos::route('/'),
            'create' => Pages\CreateComunicacaoTipo::route('/create'),
            'edit' => Pages\EditComunicacaoTipo::route('/{record}/edit'),
        ];
    }
}
