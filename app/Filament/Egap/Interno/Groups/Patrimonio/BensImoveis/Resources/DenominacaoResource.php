<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\DenominacaoResource\Pages\ListDenominacaos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Denominacao;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DenominacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Denominacao::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Denominação';
    protected static ?string $modelLabel = 'Denominação';
    protected static ?string $pluralModelLabel = 'Denominação';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 11;
    protected static ?string $slug = 'bens-imoveis/denominacoes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('denominacao')
                    ->label('Denominação')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('denominacao', 'Denominação')
                    ->alignLeft(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDenominacaos::route('/'),
        ];
    }
}
