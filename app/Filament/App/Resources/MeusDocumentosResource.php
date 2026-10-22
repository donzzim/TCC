<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MeusDocumentosResource\Pages;
use App\Models\Escola\Documentos;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeusDocumentosResource extends Resource
{
    protected static ?string $model = Documentos::class;

    protected static ?string $recordTitleAttribute = 'titulo';

    protected static ?string $modelLabel = 'Meus Documentos';

    protected static ?string $puralModelLabel = 'Meus Documentos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Meus Documentos';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (! Filament::auth()->check()) {
            return false;
        }
        if (User::find(Filament::auth()->id())?->isInstrutor()) {
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tipodocumento_id')
                    ->relationship(
                        name: 'tipodocumento',
                        titleAttribute: 'descricao',
                        modifyQueryUsing: fn (Builder $query) => $query->where('grupo', '=', 'TIPO_DOCUMENTOS'),
                    )
                    ->searchable(['nome'])
                    ->label('Tipo de Documento')
                    ->required()
                    ->preload()
                    ->columnSpan(2),
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(3),
                Forms\Components\FileUpload::make('arquivo')
                    ->label('Arquivo')
                    ->required()
                    ->columnSpan(3),
            ])
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $userId = Filament::auth()->id();
                $query->where('user_id', $userId);
            })
            ->columns([
                Tables\Columns\TextColumn::make('tipodocumento.descricao')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('arquivo')
                    ->label('Arquivo'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListMeusDocumentos::route('/'),
            'create' => Pages\CreateMeusDocumentos::route('/create'),
            'edit' => Pages\EditMeusDocumentos::route('/{record}/edit'),
        ];
    }
}
