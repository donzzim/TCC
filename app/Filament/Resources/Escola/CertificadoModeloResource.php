<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Escola\CertificadoModeloResource\Pages;
use App\Filament\Resources\CertificadoModeloResource\RelationManagers;
use App\Filament\Resources\UserResource;
use App\Models\Escola\CertificadoModelos;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CertificadoModeloResource extends Resource
{
    protected static ?string $model = CertificadoModelos::class;
    // protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Certificado Modelo';
    protected static ?string $puralModelLabel = 'Certificado Modelos';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationLabel = 'Certificado Modelos';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 6;

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

            Tables\Columns\ImageColumn::make('arquivo')
                ->label('Arquivo')
                ->disk('public'),

            Tables\Columns\TextColumn::make('assinado.name')
                ->label('Assinado por')
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
                ->columnSpan(3),

            Forms\Components\RichEditor::make('texto')
                ->label('Texto')
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('arquivo')
                ->label('Arquivos')
                ->columnSpan(2),

            Forms\Components\Select::make('assinado_por')
                ->relationship(
                    name: 'assinado',
                    titleAttribute: 'name',
                )
                ->searchable(['name'])
                ->label('Assinado por')
                ->preload()
                ->createOptionForm(
                    UserResource::getFormFields()
                )
                ->columnSpan(3),
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
            'index' => Pages\ListCertificadoModelos::route('/'),
            'create' => Pages\CreateCertificadoModelo::route('/create'),
            'edit' => Pages\EditCertificadoModelo::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->titulo;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['titulo', 'texto'];
    }
}
