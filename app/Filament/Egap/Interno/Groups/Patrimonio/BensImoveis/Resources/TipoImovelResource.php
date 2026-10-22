<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource\Pages\CreateTipoImovel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource\Pages\EditTipoImovel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoImovelResource\Pages\ListTipoImovels;
use App\Filament\Egap\Resources\Patrimonio\BensImoveis\TipoImovelResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\TipoImovel;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TipoImovelResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = TipoImovel::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Tipo de Imóvel';
    protected static ?string $modelLabel = 'Tipo de Imóvel';
    protected static ?string $pluralModelLabel = 'Tipos de Imóvel';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 17;
    protected static ?string $slug = 'bens-imoveis/tipos-imoveis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('desc_tipo_imovel')
                            ->label('Descrição')
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
                TableColumns::text('desc_tipo_imovel', 'Descrição'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTipoImovels::route('/'),
            'create' => CreateTipoImovel::route('/create'),
            'edit' => EditTipoImovel::route('/{record}/edit'),
        ];
    }
}
