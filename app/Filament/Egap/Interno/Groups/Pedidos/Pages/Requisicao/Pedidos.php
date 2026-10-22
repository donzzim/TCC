<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao;

use App\Filament\Egap\Interno\Clusters\PedidosCluster;
use App\Filament\Egap\Interno\Permissions\Pedidos\PedidoPage;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use App\Models\egap\Almoxarifado\Pedidos as PedidosMaterialPermanente;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class Pedidos extends PedidoPage implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = PedidosCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string | \UnitEnum | null $navigationGroup = 'Requisição';

    protected static ?string $title = 'Pedidos - Materiais Permanentes';

    protected static ?string $slug = 'pedidos-materiais-permanentes';

    protected static ?string $navigationLabel = 'Pedidos';

    protected string $view = 'egap.livewire.support.filament-page-table';

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('id', 'DESC')
            ->query(
                PedidosMaterialPermanente::query()
                    ->whereHas('itens.materialRef', fn ($query) => $query->where('id_tipo_material', 'P'))
            )
//            ->query(
//                PedidosMaterialPermanente::query()
//                    ->whereHas('itens.materialRef', fn ($query) => $query->where('id_tipo_material', 'P'))
//                    ->with(['itens' => fn ($query) => $query->whereHas('materialRef', fn ($q) => $q->where('id_tipo_material', 'P'))])
//            )
            ->columns([
                TableColumns::text('id', '#', isFirstColumn: true, isIdColumn: true),
                TableColumns::text('num_protocolo', 'Protocolo')
                    ->badge(),
                TableColumns::dateTime('date_time', 'Data Pedido')
                    ->formatStateUsing(fn ($record) => $record->date_time->format('d/m/Y'))
                    ->description(fn ($record) => $record->date_time->format('H:i')),
                TableColumns::text('solicitante_get.name', 'Solicitante'),
                TableColumns::text('unidade_judiciaria.unidadeorganizacional', 'Setor', true)
                    ->description(fn($record) => $record->setor_get->setor),
                TableColumns::text('itens.materialRef.Descricao', 'Material', true)
                    ->listWithLineBreaks(),
                TableColumns::text('justificativa', 'Justificativa'),
                TableColumns::text('itens.QuantidadeMaterial', 'Quantidade Solicitada')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks(),
                TableColumns::text('itens.QuantidadeMaterialAtendida', 'Quantidade Atendida')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks(),
                TableColumns::text('itens.quantidade_validada', 'Quantidade Validada')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks(),
                TableColumns::text('itens.situacaoRef.Descricao', 'Situação Material')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks()
                    ->color(function (mixed $state, $record): string {
                        $situacao = $record->itens
                            ->first(fn ($item) => $item->situacaoRef?->Descricao === $state)
                            ?->situacao;

                        return match ((int) $situacao) {
                            4, 5     => 'danger',
                            3, 7, 12 => 'success',
                            6, 9     => 'warning',
                            default  => 'gray',
                        };
                    }),
                TableColumns::text('itens.ObservacaoItem', 'Observação')
                    ->limit(40)
                    ->listWithLineBreaks()
                    ->tooltip(function ($record): ?HtmlString {
                        $observacoes = $record->itens
                            ->pluck('ObservacaoItem')
                            ->filter()
                            ->map(fn ($obs) => e($obs))
                            ->implode('<br>');

                        return filled($observacoes) ? new HtmlString($observacoes) : null;
                    }),
            ])
            ->recordActions([
                TableModalAction::make('materiais_pedido')
                    ->icon(Heroicon::OutlinedCube)
                    ->label('Materiais')
                    ->modalHeading(fn ($record): string => "Itens do Pedido #{$record->id}")
                    ->modalContent(fn ($record): HtmlString => new HtmlString(
                        Livewire::mount(
                            'pedidos.validar-pedido-itens-modal',
                            ['pedidoId' => (int) $record->id],
                            "validar-pedido-itens-{$record->getKey()}",
                        )
                    )),
            ])
            ->toolbarActions([]);

    }
}
