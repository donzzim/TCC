<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ModelosResource\Pages\ListModelos;
use App\Filament\Egap\Resources\Cadastro\ModelosResource\Pages;
use App\Filament\Egap\Resources\Cadastro\ModelosResource\RelationManagers;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\Modelos;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class ModelosResource extends CadastroResource
{
    protected static ?string $model = Modelos::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';
    protected static ?string $navigationLabel = 'Modelos';
    protected static ?string $modelLabel = 'Modelo';
    protected static ?string $pluralModelLabel = 'Modelos';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('marca')
                    ->label('Marca')
                    ->relationship('marca_ref', 'descricao')
                    ->searchable()
                    ->preload()
                    ->columnSpanFull()
                    ->required(),

                TextInput::make('descricao')
                    ->label('Descrição do Modelo')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('marca_ref.descricao', 'Marca', isFirstColumn: true)
                    ->default('Sem marca'),
                TableColumns::text('descricao', 'Modelo'),
                TableColumns::updatedBy('atualizado_por.name'),
            ])
            ->defaultSort('descricao');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModelos::route('/'),
        ];
    }
}
