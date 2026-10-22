<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource\Pages\CreateEstadoConservacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource\Pages\EditEstadoConservacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\EstadoConservacaoResource\Pages\ListEstadoConservacaos;
use App\Filament\Egap\Resources\Patrimonio\BensImoveis\EstadoConservacaoResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\EstadoConservacao;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EstadoConservacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = EstadoConservacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Estado de Conservação';
    protected static ?string $modelLabel = 'Estado de Conservação';
    protected static ?string $pluralModelLabel = 'Estados de Conservação';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 12;
    protected static ?string $slug = 'bens-imoveis/estado-conservacao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('descEstadoConservacao')
                            ->label('Estado de Conservação')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('Id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('descEstadoConservacao', 'Estado de Conservação'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEstadoConservacaos::route('/'),
            'create' => CreateEstadoConservacao::route('/create'),
            'edit' => EditEstadoConservacao::route('/{record}/edit'),
        ];
    }
}
