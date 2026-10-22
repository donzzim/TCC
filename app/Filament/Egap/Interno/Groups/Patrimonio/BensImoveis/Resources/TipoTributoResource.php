<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource\Pages\CreateTipoTributo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource\Pages\EditTipoTributo;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\TipoTributoResource\Pages\ListTipoTributos;
use App\Filament\Egap\Resources\Patrimonio\BensImoveis\TipoTributoResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\TipoTributo;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TipoTributoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = TipoTributo::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Tipo de Tributo';
    protected static ?string $modelLabel = 'Tipo de Tributo';
    protected static ?string $pluralModelLabel = 'Tipos de Tributo';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 18;
    protected static ?string $slug = 'bens-imoveis/tipos-tributos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('descricao')
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
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('descricao', 'Descrição'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTipoTributos::route('/'),
            'create' => CreateTipoTributo::route('/create'),
            'edit' => EditTipoTributo::route('/{record}/edit'),
        ];
    }
}
