<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CondicaoUsoResource\Pages\ListCondicaoUsos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\CondicaoUso;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CondicaoUsoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = CondicaoUso::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Condição de Uso';
    protected static ?string $modelLabel = 'Condição de Uso';
    protected static ?string $pluralModelLabel = 'Condições de Uso';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'bens-imoveis/condicoes-uso';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
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
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('descricao', 'Descrição')
                    ->alignLeft(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCondicaoUsos::route('/'),
        ];
    }
}
