<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources;

use App\Filament\Egap\Interno\Clusters\AlmoxarifadoCluster;
use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\TipoMovimentacaoNotaFiscalResource\Pages\ListTipoMovimentacaoNotaFiscals;
use App\Filament\Egap\Interno\Permissions\Almoxarifado\AlmoxarifadoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\TipoMovimentacaoNotaFiscal;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TipoMovimentacaoNotaFiscalResource extends AlmoxarifadoResource
{
    protected static ?string $model = TipoMovimentacaoNotaFiscal::class;
    protected static ?string $cluster = AlmoxarifadoCluster::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $slug = 'tipo-movimentacao';
    protected static ?string $navigationLabel = 'Tipo de Movimentação';
    protected static ?string $pluralLabel = 'Tipos de Movimentação';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 5;

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
            'index' => ListTipoMovimentacaoNotaFiscals::route('/'),
        ];
    }
}
