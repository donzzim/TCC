<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources;

use App\Filament\Egap\Interno\Clusters\AlmoxarifadoCluster;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\SituacaoNotaFiscalResource\Pages\ListSituacaoNotaFiscals;
use App\Filament\Egap\Interno\Permissions\Almoxarifado\AlmoxarifadoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\SituacaoNotaFiscal;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SituacaoNotaFiscalResource extends AlmoxarifadoResource
{
    protected static ?string $model = SituacaoNotaFiscal::class;
    protected static ?string $cluster = AlmoxarifadoCluster::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $slug = 'situacao-notas-fiscais';
    protected static ?string $navigationLabel = 'Situação da Nota Fiscal';
    protected static ?string $pluralLabel = 'Situações da Nota Fiscal';
    protected static ?string $pluralModelLabel = 'Situações da Nota Fiscal';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('descricao', 'Descrição', true)
                    ->searchable(),
                TableColumns::updatedBy('atualizadoPor.name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSituacaoNotaFiscals::route('/'),
        ];
    }
}
