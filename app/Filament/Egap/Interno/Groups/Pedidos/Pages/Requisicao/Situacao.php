<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao;

use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao\SituacaoResource\Pages\ListSituacaoPage;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoResource;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\SituacaoPedido;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class Situacao extends PedidoResource
{
    protected static ?string $model = SituacaoPedido::class;

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static string | \UnitEnum | null $navigationGroup = 'Requisição';
    protected static ?string $title = 'Pedidos - Situação';
    protected static ?string $label = 'Pedidos - Situação';
    protected static ?string $pluralLabel = 'Pedidos - Situações';
    protected static ?string $slug = 'situacao-pedidos';
    protected static ?string $navigationLabel = 'Situação';
    protected static ?string $breadcrumb = 'Situação dos Pedidos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Descricao')
                    ->label('Descrição')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255)
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('id','#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('Descricao','Descrição'),
                TableColumns::updatedBy('atualizadoPor.name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSituacaoPage::route('/'),
        ];
    }
}
