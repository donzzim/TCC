<?php

namespace App\Filament\Egap\Interno\Groups\Cadastro\Resources;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\ElementoDespesaResource\Pages\ListElementoDespesas;
use App\Filament\Egap\Resources\Cadastro\ElementoDespesaResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\ElementoDespesa;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Egap\Interno\Permissions\Cadastro\CadastroResource;

class ElementoDespesaResource extends CadastroResource
{
    protected static ?string $model = ElementoDespesa::class;
    protected static ?int $navigationSort = 1;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Elemento de Despesa';
    protected static ?string $modelLabel = 'Elemento de Despesa';
    protected static ?string $pluralModelLabel = 'Elementos de Despesa';
    protected static string | \UnitEnum | null $navigationGroup = 'Cadastro';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('CodigodaClasse')
                    ->label('Código da Classe')
                    ->numeric()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),

                TextInput::make('DescricaodaClasse')
                    ->label('Descrição da Classe')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('Despesa')
                    ->label('Despesa')
                    ->maxLength(2)
                    ->required(),

                TextInput::make('VidaUtil')
                    ->label('Vida Útil (anos)')
                    ->numeric()
                    ->minValue(0),

                TextInput::make('ValorResidual')
                    ->label('Valor Residual')
                    ->numeric()
                    ->step(0.0000000001)
                    ->prefix('R$'),

                TextInput::make('item_patrimonial')
                    ->label('Item Patrimonial')
                    ->maxLength(255),
            ])
            ->columns(2);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('CodigodaClasse', 'Código', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('DescricaodaClasse', 'Descrição')
                    ->limit(40),
                TableColumns::text('Despesa')
                    ->badge(),
                TableColumns::text('VidaUtil', 'Vida Útil'),
                TableColumns::updatedBy('atualizado_por.name', 'Usuário'),
                TableColumns::money('ValorResidual')
            ])
            ->defaultSort('CodigodaClasse');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListElementoDespesas::route('/'),
        ];
    }
}
