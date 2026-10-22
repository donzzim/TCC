<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\UnidadesDeMedidaResource\Pages\ListUnidadesDeMedida;
use App\Filament\Egap\Resources\Cadastro\UnidadesDeMedidaResource\Pages;
use App\Filament\Egap\Resources\Cadastro\UnidadesDeMedidaResource\RelationManagers;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\UnidadesDeMedida;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class UnidadesDeMedidaResource extends CadastroResource
{
    protected static ?string $model = UnidadesDeMedida::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Unidades de Medida';
    protected static ?string $modelLabel = 'Unidade de Medida';
    protected static ?string $pluralModelLabel = 'Unidades de Medida';
    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Sigla')
                    ->label('Sigla')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(2),

                TextInput::make('Unidade')
                    ->label('Descrição da Unidade')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(100),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('Sigla', isFirstColumn: true),
                TableColumns::text('Unidade'),
                TableColumns::updatedBy('atualizado_por.name'),
            ])
            ->defaultSort('Sigla')
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnidadesDeMedida::route('/'),
        ];
    }
}
