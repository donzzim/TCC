<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Components;

use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Almoxarifado\ItemPedido;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;

class PedidosEmAbertoTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?int $selectedItemPedidoId = null;

    #[On('limpar-selecao-pedidos')]
    public function limparSelecaoPedidos(): void
    {
        $this->selectedItemPedidoId = null;
    }

    #[On('refresh-pedidos-table')]
    public function refreshPedidosTable(): void
    {
        // Apenas forca o rerender.
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->heading('Pedidos em aberto')
            ->query($this->getPedidosQuery())
            ->defaultSort('idPedido', 'desc')
            ->recordClasses(function (ItemPedido $record): string {
                return $this->selectedItemPedidoId === (int) $record->id
                    ? 'bg-primary-50 dark:bg-primary-900/20'
                    : '';
            })
            ->columns([
                TextColumn::make('idPedido')
                    ->label('Pedido')
                    ->description(
                        fn(ItemPedido $record) => $record->id
                            ? "Item: {$record->id}"
                            : null
                    )
                    ->badge()
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('idPedido', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "%{$search}%");
                        });
                    }),

                TextColumn::make('unidade')
                    ->label('Unidade/Setor')
                    ->getStateUsing(fn(ItemPedido $record): string => $record->idPedidoRef?->setor_get?->unidadeorganizacional ?? '-')
                    ->description(fn(ItemPedido $record): ?string => $record->idPedidoRef?->setor_get?->setor)
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->whereHas('idPedidoRef.setor_get', function (Builder $setorQuery) use ($search): void {
                                    $setorQuery
                                        ->where('unidadeorganizacional', 'like', "%{$search}%")
                                        ->orWhere('Setor', 'like', "%{$search}%");
                                })
                                ->orWhereHas('idPedidoRef.complementoSetor', fn(Builder $complementoQuery) => $complementoQuery->where('descricao', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('material')
                    ->label('Material')
                    ->getStateUsing(fn(ItemPedido $record): string => $this->materialPedido($record))
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->whereHas('descricaoDetalhadaRef', fn(Builder $detalhadaQuery) => $detalhadaQuery->where('descricao_detalhada', 'like', "%{$search}%"))
                                ->orWhereHas('materialRef', fn(Builder $materialQuery) => $materialQuery->where('Descricao', 'like', "%{$search}%"))
                                ->orWhereHas('descricaoDetalhadaRef.descricao_resumida_text', fn(Builder $resumidaQuery) => $resumidaQuery->where('Descricao', 'like', "%{$search}%"));
                        });
                    }),

                TextColumn::make('QuantidadeMaterial')
                    ->label('Solicitada')
                    ->badge()
                    ->alignEnd(),

                TextColumn::make('quantidade_validada')
                    ->label('Validada')
                    ->formatStateUsing(fn($state): int => (int) ($state ?? 0))
                    ->badge()
                    ->alignEnd()
                    ->color('info'),

                TextColumn::make('QuantidadeMaterialAtendida')
                    ->label('Atendida')
                    ->badge()
                    ->alignEnd()
                    ->color('success'),

                TextColumn::make('situacaoRef.Descricao')
                    ->label('Situacao')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('selecionar')
                    ->label(function (ItemPedido $record): string {
                        return $this->selectedItemPedidoId === (int) $record->id
                            ? 'Desmarcar'
                            : 'Selecionar';
                    })
                    ->icon(function (ItemPedido $record): string {
                        return $this->selectedItemPedidoId === (int) $record->id
                            ? 'heroicon-o-x-mark'
                            : 'heroicon-o-check-circle';
                    })
                    ->color(function (ItemPedido $record): string {
                        return $this->selectedItemPedidoId === (int) $record->id
                            ? 'gray'
                            : 'primary';
                    })
                    ->action(function (ItemPedido $record): void {
                        $itemId = (int) $record->id;

                        if ($this->selectedItemPedidoId === $itemId) {
                            $this->selectedItemPedidoId = null;

                            $this->dispatch(
                                'pedido-selecionado',
                                pedidoId: 0,
                                itemPedidoId: 0,
                                protocolo: '-',
                                solicitante: '-',
                                destino: '-',
                                material: '-',
                                materialId: 0,
                                materialResumo: '-',
                                situacao: '-',
                                quantidadeSolicitada: 0,
                                quantidadeValidada: 0,
                                quantidadeAtendida: 0,
                            );

                            return;
                        }

                        $this->selectedItemPedidoId = $itemId;

                        $this->dispatch(
                            'pedido-selecionado',
                            pedidoId: (int) $record->idPedido,
                            itemPedidoId: $itemId,
                            protocolo: '-',
                            solicitante: '-',
                            destino: $this->destinoPedido($record) ?: '-',
                            material: $this->materialPedido($record),
                            materialId: $this->materialResumoId($record),
                            materialResumo: $this->descricaoResumidaPedido($record),
                            situacao: (string) ($record->situacaoRef?->Descricao ?? '-'),
                            quantidadeSolicitada: (int) ($record->QuantidadeMaterial ?? 0),
                            quantidadeValidada: (int) ($record->quantidade_validada ?? 0),
                            quantidadeAtendida: (int) ($record->QuantidadeMaterialAtendida ?? 0),
                        );
                    }),
            ])
            ->toolbarActions([]);
    }

    private function destinoPedido(ItemPedido $record): string
    {
        return collect([
            $record->idPedidoRef?->setor_get?->unidadeorganizacional,
            $record->idPedidoRef?->setor_get?->setor,
            $record->idPedidoRef?->complementoSetor?->descricao,
        ])->filter()->implode(' / ');
    }

    private function materialResumoId(ItemPedido $record): int
    {
        $descricaoDetalhadaResumoId = (int) ($record->descricaoDetalhadaRef?->descricao_resumida ?? 0);

        return $descricaoDetalhadaResumoId > 0
            ? $descricaoDetalhadaResumoId
            : (int) ($record->material ?? 0);
    }

    private function materialPedido(ItemPedido $record): string
    {
        $descricaoDetalhada = trim((string) ($record->descricaoDetalhadaRef?->descricao_detalhada ?? ''));

        if ($descricaoDetalhada !== '') {
            return $descricaoDetalhada;
        }

        return $record->materialRef?->Descricao
            ?? $record->descricaoDetalhadaRef?->descricao_resumida_text?->Descricao
            ?? '-';
    }

    private function descricaoResumidaPedido(ItemPedido $record): string
    {
        return $record->descricaoDetalhadaRef?->descricao_resumida_text?->Descricao
            ?? $record->materialRef?->Descricao
            ?? '-';
    }

    protected function getPedidosQuery(): Builder
    {
        return ItemPedido::query()
            ->with([
                'idPedidoRef.setor_get',
                'idPedidoRef.complementoSetor',
                'materialRef',
                'descricaoDetalhadaRef.descricao_resumida_text',
                'situacaoRef',
            ])
            ->whereIn('situacao', [6, 7])
            ->whereHas('idPedidoRef', fn(Builder $query) => $query->where('setor_responsavel', 1239))
            ->where(function (Builder $query): void {
                $query
                    ->where(function (Builder $subQuery): void {
                        $subQuery
                            ->whereNotNull('material')
                            ->where('material', '<>', 0);
                    })
                    ->orWhereHas('descricaoDetalhadaRef', function (Builder $subQuery): void {
                        $subQuery
                            ->whereNotNull('descricao_resumida')
                            ->where('descricao_resumida', '<>', 0);
                    });
            });
    }

    public function render(): View
    {
        return view('egap.livewire.atendimento-pedidos.pedidos-em-aberto-table');
    }
}
