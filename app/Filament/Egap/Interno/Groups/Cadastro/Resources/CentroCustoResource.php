<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\CentroCustoResource\Pages\ListCentroCustos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\CentroCusto;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class CentroCustoResource extends CadastroResource
{
    protected static ?string $model = CentroCusto::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?string $navigationLabel = 'Centro de Custo (SIGEFES)';
    protected static bool $isDiscovered = false;
    protected static ?string $modelLabel = 'Centro de Custo';
    protected static ?string $pluralModelLabel = 'Centros de Custo';

    //    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                TextInput::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('codigo', 'Código'),
                TableColumns::text('descricao', 'Descrição')
                    ->wrap(),
            ])
            ->defaultSort('codigo');
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
            'index' => ListCentroCustos::route('/'),
        ];
    }
}
