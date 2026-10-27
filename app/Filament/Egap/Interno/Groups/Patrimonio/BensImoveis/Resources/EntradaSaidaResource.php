<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EntradaSaidaResource\Pages\ListEntradaSaidas;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\EntradaSaida;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EntradaSaidaResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = EntradaSaida::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Entradas/Saídas';
    protected static ?string $modelLabel = 'Entrada/Saída';
    protected static ?string $pluralModelLabel = 'Entradas/Saídas';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 15;
    protected static ?string $slug = 'bens-imoveis/entrada-saida';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('tipo')
                    ->label('Tipo')
                    ->required()
                    ->native(false)
                    ->columnSpanFull()
                    ->options([
                        'Entrada' => 'Entrada',
                        'Saída' => 'Saída'
                    ])
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('descricao', 'Descrição'),
                TableColumns::text('tipo', 'Tipo'),
                TableColumns::updatedBy('usuarioRef.name', 'Usuário', format: 'd/m/Y'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEntradaSaidas::route('/'),
        ];
    }
}
