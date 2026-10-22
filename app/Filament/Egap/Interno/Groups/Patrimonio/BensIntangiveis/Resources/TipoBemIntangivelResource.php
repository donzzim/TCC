<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\TipoBemIntangivelResource\Pages\CreateTipoBemIntangivel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\TipoBemIntangivelResource\Pages\EditTipoBemIntangivel;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensIntangiveis\Resources\TipoBemIntangivelResource\Pages\ListTipoBemIntangivels;
use App\Filament\Egap\Resources\Patrimonio\BensIntangiveis\TipoBemIntangivelResource\Pages;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensIntangiveis\TipoBemIntagivel;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TipoBemIntangivelResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = TipoBemIntagivel::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Tipos de Intangíveis';

    protected static ?string $modelLabel = 'Tipo de Bem Intangível';

    protected static ?string $pluralModelLabel = 'Tipos de Bens Intangíveis';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Intangíveis';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'bens-intangiveis/tipos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->placeholder('Ex.: Software, licença, marca ou patente')
                    ->prefixIcon('heroicon-o-tag')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('descricao', 'Tipo de Bem Intangível', isFirstColumn: true)
                    ->icon('heroicon-o-tag')
                    ->weight('medium')
                    ->wrap(),
                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->defaultSort('descricao');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTipoBemIntangivels::route('/'),
        ];
    }
}
