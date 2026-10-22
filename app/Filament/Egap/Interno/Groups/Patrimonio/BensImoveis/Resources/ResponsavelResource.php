<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ResponsavelResource\Pages\CreateResponsavel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ResponsavelResource\Pages\EditResponsavel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensImoveis\Resources\ResponsavelResource\Pages\ListResponsavels;
use App\Filament\Egap\Resources\Patrimonio\BensImoveis\ResponsavelResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensImoveis\Responsavel;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ResponsavelResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Responsavel::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Responsável';
    protected static ?string $modelLabel = 'Responsável';
    protected static ?string $pluralModelLabel = 'Responsáveis';
    protected static string | \UnitEnum | null $navigationGroup = 'Bens Imóveis';
    protected static ?int $navigationSort = 13;
    protected static ?string $slug = 'bens-imoveis/responsaveis';

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

                        TextInput::make('proprietario')
                            ->label('Proprietário')
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
                TableColumns::text('proprietario', 'Proprietário'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResponsavels::route('/'),
            'create' => CreateResponsavel::route('/create'),
            'edit' => EditResponsavel::route('/{record}/edit'),
        ];
    }
}
