<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource\Pages\CreateCidUf;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource\Pages\EditCidUf;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\CidUfResource\Pages\ListCidUfs;
use App\Filament\Egap\Resources\Patrimonio\BensImoveis\CidUfResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\CidUf;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CidUfResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = CidUf::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Cidade/UF';
    protected static ?string $modelLabel = 'Cidade/UF';
    protected static ?string $pluralModelLabel = 'Cidades/UF';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 9;
    protected static ?string $slug = 'bens-imoveis/cidades-uf';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('id_cidade')
                            ->label('Cidade')
                            ->numeric()
                            ->required(),

                        TextInput::make('cd_uf')
                            ->label('cd uf')
                            ->maxLength(2)
                            ->required(),

                        TextInput::make('cd_cep_cidade')
                            ->label('cd cep cidade')
                            ->maxLength(20)
                            ->required(),
                    ])
                    ->columns(1)
            ]);
    }
    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id_cidade', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('cd_uf', 'UF'),
                TableColumns::text('cd_cep_cidade', 'CEP'),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCidUfs::route('/'),
            'create' => CreateCidUf::route('/create'),
            'edit' => EditCidUf::route('/{record}/edit'),
        ];
    }
}
