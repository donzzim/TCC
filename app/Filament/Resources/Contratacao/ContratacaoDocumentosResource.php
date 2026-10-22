<?php

namespace App\Filament\Resources\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Contratacao\ContratacaoDocumentosResource\Pages;
use App\Filament\Resources\Contratacao\ContratacaoDocumentosResource\RelationManagers;
use App\Models\Contratacao\ContratacaoDocumento;
use App\Models\Contratacao\TipoProcesso;
use App\Models\Escola\TipoPublico;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContratacaoDocumentosResource extends Resource
{
    protected static ?string $model = ContratacaoDocumento::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $recordTitleAttribute = 'modelo_id';
    protected static ?string $modelLabel = 'Documentos ';
    protected static ?string $puralModelLabel = 'Documentos ';
    protected static ?string $navigationLabel = 'Documentos ';
    protected static string | UnitEnum | null $navigationGroup = 'Contratação';
    protected static ?int $navigationSort = 9;


    public static array $options = [
        // 'App\Models\Contratacao\TipoProcesso' => [
        //     'label' => 'Tipo de Processo',
        //     'records' => TipoProcesso::all()->pluck('descricao', 'id'),
        // ],
        // 'App\Models\Contratacao\TipoPublico' => [
        //     'label' => 'Tipo de Publico',
        //     'records' => TipoPublico::all()->pluck('descricao', 'id'),
        // ],
    ];

    public static function getFormfields(): array
    {
        return [
            Forms\Components\Select::make('modelable_type')
                ->options(
                    function () {
                        $opt = [];
                        foreach (static::$options as $key => $option) {
                            $opt[$key] = $option['label'];
                        }
                        return $opt;
                    }
                )
                ->live(),

            Forms\Components\Select::make('modelable_id')
                ->label('Tipo de Processo')
                ->options(
                    []
                )
                ->preload()
                ->required(),

            Forms\Components\Select::make('modelo_id')
                ->label('Modelo')
                ->relationship('modelo', 'nome')
                ->preload()
                ->searchable(),

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
                Tables\Columns\TextColumn::make('modelable.descricao')
                    ->label('Tipo de Processo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('modelo.nome')
                    ->label('Modelos')
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
            'index' => Pages\ListContratacaoDocumentos::route('/'),
            'create' => Pages\CreateContratacaoDocumentos::route('/create'),
            'edit' => Pages\EditContratacaoDocumentos::route('/{record}/edit'),
        ];
    }
}
