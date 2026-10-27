<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\SituacaoResource\Pages\ListSituacaos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Situacao;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SituacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Situacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Situação';
    protected static ?string $modelLabel = 'Situação';
    protected static ?string $pluralModelLabel = 'Situações';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 14;
    protected static ?string $slug = 'bens-imoveis/situacao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Descricao')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('Id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('Descricao', 'Descrição')
                    ->alignLeft(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSituacaos::route('/'),
        ];
    }
}
