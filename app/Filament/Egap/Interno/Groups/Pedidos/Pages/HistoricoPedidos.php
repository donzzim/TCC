<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages;

use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoPage;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\FasePedido;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class HistoricoPedidos extends PedidoPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $model = FasePedido::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $title = 'Histórico de Pedidos';

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Histórico de Pedidos';

    protected static ?string $slug = 'historico-pedidos';

    protected string $view = 'egap.livewire.support.filament-page-table';

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query(
                FasePedido::query()
            )
            ->defaultSort('date_time', 'DESC')
            ->recordActions([])
            ->toolbarActions([])
            ->columns([
                TableColumns::date('date_time', 'Data/Hora', isFirstColumn: true)
                    ->description(fn (FasePedido $pedido) => date('H:i', strtotime($pedido->date_time))),

                TableColumns::text('termoRef.arquivoDigital.arquivo_digital', 'Termo')
                    ->searchable(['termoRef.num_termo', 'termoRef.ano_termo'])
                    ->weight('medium')
                    ->formatStateUsing(fn (FasePedido $record) => $record->id_termo ? 'Abrir Termo' : '-')
                    ->color(fn (FasePedido $record): ?string => $record->id_termo ? 'primary' : 'gray')
                    ->tooltip(fn (FasePedido $record) => $record->id_termo ? 'Clique para visualizar o termo' : '')
                    ->url(fn (FasePedido $record): ?string => $record->termoRef?->arquivoDigital?->url)
                    ->openUrlInNewTab(),

                TableColumns::text('pedidoRef.id', 'Pedido', isIdColumn: true)
                    ->color('gray'),

                TableColumns::text('itemPedidoRef.id', 'Item Pedido', isIdColumn: true)
                    ->color('gray'),

                TableColumns::text('descricaoResumidaRef.Descricao', 'Material'),

                TableColumns::text('descricaoDetalhadaRef.descricao_detalhada', 'Descrição Detalhada'),

                TableColumns::text('quantidade', 'Quantidade')
                    ->badge(),

                TableColumns::text('Descricao', 'Descrição')
                    ->badge(),

                TableColumns::updatedBy('usuarioRef.name', 'Usuário'),
            ])
            ->filters([
                Filter::make('termo')
                    ->schema([
                        TextInput::make('termo_id')
                            ->label('Nº do Termo')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['termo_id'],
                            fn($query, $value) =>
                            $query->whereHas('termoRef', fn($q) => $q->where('id', $value))
                        );
                    }),

                Filter::make('pedido')
                    ->schema([
                        TextInput::make('pedido_id')
                            ->label('Nº do Pedido')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['pedido_id'],
                            fn($query, $value) =>
                            $query->whereHas('pedidoRef', fn($q) => $q->where('id', $value))
                        );
                    }),
            ]);
    }
}
