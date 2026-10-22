<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\RecadastroResource\Pages;
use App\Filament\Resources\RecadastroResource\RelationManagers;
use App\Models\Recadastro;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecadastroResource extends Resource
{
    protected static ?string $model = Recadastro::class;
    protected static ?string $recordTitleAttribute = 'nm_pessoa';
    protected static ?string $modelLabel = 'Cadastro PJES ';
    protected static ?string $puralModelLabel = 'Cadastro PJES ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Cadastro PJES ';
    protected static string | UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('cpf')
                    ->required()
                    ->mask('999.999.999-99')
                    ->numeric(),
                Forms\Components\TextInput::make('nome')
                    ->maxLength(255),
                Forms\Components\TextInput::make('telefone_celular')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cpf')
                    ->formatStateUsing(fn($state) => $state ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', str_pad($state, 11, '0', STR_PAD_LEFT)) : '')
                    ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->wrap()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('telefone_celular')
                    ->wrapHeader()
                    // ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    // ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_pessoal')
                    ->wrapHeader()
                    // ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('centrocusto')
                    // ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipovinc')
                    ->label('Vinculo')
                    // ->searchable()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cargo')
                    ->searchable()
                    ->wrap()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('funcao')
                    ->searchable()
                    ->wrap()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cd_orgao')
                    // ->searchable()
                    ->wrap()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomesetor')
                    ->searchable()
                    ->wrap()
                    ->toggleable()
                    ->sortable(),
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
            'index' => Pages\ListRecadastros::route('/'),
            'create' => Pages\CreateRecadastro::route('/create'),
            'edit' => Pages\EditRecadastro::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        // return $record->nome;
        return $record->nm_pessoa;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            // 'nome',
            // 'cpf',
            // 'categoria',
            // 'lotacao',
            'nm_pessoa',
            'no_cpf_pessoa',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            // 'Nome' => $record->nome,
            // 'Categoria' => $record->categoria,
            // 'Lotação' => $record->lotacao,
            'Nome' => $record->nm_pessoa,
            // 'Categoria' => $record->categoria,
            // 'Lotação' => $record->lotacao,
        ];
    }
    // public static function getGlobalSearchEloquentQuery(): Builder
    // {
    //     return parent::getGlobalSearchEloquentQuery()->with(['instrutores', 'recursos', 'publico']);
    // }
}
