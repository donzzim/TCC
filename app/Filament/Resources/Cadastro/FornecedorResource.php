<?php

namespace App\Filament\Resources\Cadastro;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Cadastro\FornecedorResource\Pages;
use App\Filament\Resources\FornecedorResource\RelationManagers;
use App\Models\Cadastro\Fornecedores;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FornecedorResource extends Resource
{
    protected static ?string $model = Fornecedores::class;
    protected static ?string $recordTitleAttribute = 'razao_social';
    protected static ?string $modelLabel = 'Fornecedor ';
    protected static ?string $puralModelLabel = 'Fornecedores ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Fornecedores ';
    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 3;
    public static function getFormfields(): array
    {
        return [
            Forms\Components\TextInput::make('cnpj')
                ->label('CNPJ')
                ->mask('99.999.999/9999-99')
                ->required()
                ->columnSpan(1),

            Forms\Components\TextInput::make('razao_social')
                ->label('Razão Social')
                ->columnSpan(1),

            Forms\Components\TextInput::make('telefone')
                ->label('Telefone')
                ->columnSpan(1),

            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->columnSpan(1),

            Forms\Components\RichEditor::make('observacao')
                ->label('Observação')
                ->required()
                ->columnSpanFull(),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormFields()
            )
            ->columns(6);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('razao_social')
                    ->label('Razão Social')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefone')
                    ->label('Telefone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ])
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
            'index' => Pages\ListFornecedors::route('/'),
            'create' => Pages\CreateFornecedor::route('/create'),
            'edit' => Pages\EditFornecedor::route('/{record}/edit'),
        ];
    }
}
