<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ObraResource\Pages\ListObras;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Obra;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ObraResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Obra::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Obras e Ampliações';
    protected static ?string $modelLabel = 'Obra e Ampliação';
    protected static ?string $pluralModelLabel = 'Obras e Ampliações';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'bens-imoveis/obras';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_imovel')
                    ->label('Imóveis')
                    ->relationship('imovelRelacaoref', 'descricao')
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),

                TextInput::make('descricao')
                    ->label('Descrição')
                    ->columnSpanFull(),

                DatePicker::make('data')
                    ->label('Data')
                    ->default(now())
                    ->displayFormat('d/m/Y'),

                MoneyInput::make('valor')
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('imovelRelacaoref.descricao', 'Imóveis', isFirstColumn: true)
                    ->badge(),
                TableColumns::text('descricao', 'Descrição')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->descricao),
                TableColumns::date('data', 'Data'),
                TableColumns::text('valor', 'Valor (R$)'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObras::route('/'),
        ];
    }
}
