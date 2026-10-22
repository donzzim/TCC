<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\FabricanteResource\Pages\CreateFabricante;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\FabricanteResource\Pages\EditFabricante;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\FabricanteResource\Pages\ListFabricantes;
use App\Filament\Egap\Resources\Patrimonio\BensIntangiveis\FabricanteResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensIntangiveis\Fabricante;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FabricanteResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Fabricante::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Fabricantes';

    protected static ?string $modelLabel = 'Fabricante';

    protected static ?string $pluralModelLabel = 'Fabricantes';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Intangíveis';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'bens-intangiveis/fabricantes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Fabricante')
                    ->placeholder('Ex.: Microsoft, Adobe ou Oracle')
                    ->prefixIcon('heroicon-o-building-office')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('descricao', 'Fabricante', isFirstColumn: true)
                    ->icon('heroicon-o-building-office')
                    ->weight('medium')
                    ->wrap(),
                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->defaultSort('descricao');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFabricantes::route('/'),
        ];
    }
}
