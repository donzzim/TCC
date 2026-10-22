<?php

namespace App\Filament\Egap\Externo\Utils;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\Almoxarifado\FasePedido;
use App\Models\egap\Almoxarifado\ItemPedido;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\UserEgap;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Base do modal "Ver itens" de um pedido do Ambiente Externo, aberto a partir
 * de {@see PedidosTable} (legado: modal_pedidos.api.php), compartilhada entre
 * {@see \App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidoItensModal} e
 * {@see \App\Filament\Egap\Externo\Groups\Patrimonio\Components\PedidoItensModal}.
 *
 * Guarda o que é idêntico nos dois: colunas base, o cancelamento de item
 * (individual ou em lote, com a mesma regra de cancelar o pedido inteiro
 * quando todos os itens forem cancelados) e a query. A cor do badge de
 * Status diverge de verdade (o Patrimônio tem mais situações possíveis), e só
 * o Patrimônio precisa de uma coluna extra de Justificativa por item (a
 * justificativa geral do Almoxarifado fica no pedido, não no item).
 */
abstract class PedidoItensModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;
    protected const STATUS_PENDENTE = 6;

    protected const STATUS_CANCELADO = 4;

    public int $pedidoId;

    public function mount(int $pedidoId): void
    {
        $this->pedidoId = $pedidoId;
    }

    abstract protected function statusColor(int $status): string;

    /**
     * Colunas extras exibidas entre "Material" e "Qtde. Solicitada / Atendida".
     * Vazio por padrão; o Patrimônio sobrescreve para exibir a Justificativa
     * por item.
     */
    protected function colunasExtras(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                TableColumns::text('material_nome', 'Material')
                    ->wrap(),

                ...$this->colunasExtras(),

                TableColumns::text('quantidades', 'Qtde. Solicitada / Atendida')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(fn(ItemPedido $record): string => "{$record->quantidade_solicitada} / {$record->quantidade_atendida}"),

                TableColumns::money('valor_material', 'Preço Unitário Médio'),

                TableColumns::money('subtotal_atendido', 'Subtotal Atendido')
                    ->getStateUsing(fn(ItemPedido $record): float => (float) $record->valor_material * $record->quantidade_atendida),

                TableColumns::text('ObservacaoItem', 'Observações')
                    ->wrap()
                    ->description(fn(ItemPedido $record): ?string => $this->observacaoDescricao($record)),

                TableColumns::text('situacaoRef.Descricao', 'Status')
                    ->badge()
                    ->color(fn(ItemPedido $record): string => $this->statusColor((int) $record->situacao)),
            ])
            ->recordActions([
                $this->cancelarItemAction(),
            ])
            ->toolbarActions([
                $this->cancelarItensBulkAction(),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn(ItemPedido $record): bool => (int) $record->situacao === self::STATUS_PENDENTE
            )
            ->paginated(false)
            ->defaultSort('situacao')
            ->emptyStateHeading('Nenhum item neste pedido');
    }

    protected function cancelarItemAction(): Action
    {
        return Action::make('cancelar_item')
            ->label('Cancelar')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn(ItemPedido $record): bool => (int) $record->situacao === self::STATUS_PENDENTE)
            ->modalHeading('Cancelar item do pedido')
            ->modalDescription('Informe o motivo do cancelamento. Essa ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Confirmar cancelamento')
            ->schema([
                Select::make('cancelado_por')
                    ->label('Responsável pelo cancelamento')
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->options(fn(): array => UserEgap::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
                Textarea::make('justificativa')
                    ->label('Justificativa do cancelamento')
                    ->required()
                    ->rows(3)
                    ->maxLength(200),
            ])
            ->action(fn(ItemPedido $record, array $data) => $this->cancelarItens(
                collect([$record]),
                $data['justificativa'],
                (int) $data['cancelado_por'],
            ));
    }

    protected function cancelarItensBulkAction(): BulkAction
    {
        return BulkAction::make('cancelar_itens')
            ->label('Cancelar selecionados')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->modalHeading('Cancelar itens do pedido')
            ->modalDescription('Informe o motivo do cancelamento. Essa ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Confirmar cancelamento')
            ->form([
                Select::make('cancelado_por')
                    ->label('Responsável pelo cancelamento')
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->options(fn(): array => UserEgap::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
                Textarea::make('justificativa')
                    ->label('Justificativa do cancelamento')
                    ->required()
                    ->rows(3)
                    ->maxLength(200),
            ])
            ->deselectRecordsAfterCompletion()
            ->action(fn(EloquentCollection $records, array $data) => $this->cancelarItens(
                $records,
                $data['justificativa'],
                (int) $data['cancelado_por'],
            ));
    }

    protected function cancelarItens(Collection $records, string $justificativa, int $responsavelId): void
    {
        $itens = $records->filter(fn(ItemPedido $item): bool => (int) $item->situacao === self::STATUS_PENDENTE);

        if ($itens->isEmpty()) {
            Notification::make()
                ->title('Nenhum item pendente selecionado para cancelamento.')
                ->warning()
                ->send();

            return;
        }

        foreach ($itens as $item) {
            $item->update([
                'situacao' => self::STATUS_CANCELADO,
                'ObservacaoItem' => $justificativa,
                'data_cancelado' => now(),
                'cancelado_por' => $responsavelId,
            ]);

            FasePedido::query()->create([
                'idSituacao' => self::STATUS_CANCELADO,
                'Descricao' => 'Item cancelado pelo solicitante via portal externo.',
                'id_pedido' => $item->idPedido,
                'id_itempedido' => $item->id,
                'id_descricaoresumida' => $item->material,
                'id_descricaodetalhada' => $item->DescricaoDetalhada,
                'quantidade' => $item->QuantidadeMaterial,
            ]);
        }

        Notification::make()
            ->title($itens->count() === 1 ? 'Item cancelado com sucesso.' : "{$itens->count()} itens cancelados com sucesso.")
            ->success()
            ->send();

        $this->cancelarPedidoSeTodosItensCancelados($responsavelId);

        $this->dispatch('pedido-item-cancelado');
    }

    protected function cancelarPedidoSeTodosItensCancelados(int $responsavelId): void
    {
        $totalItens = ItemPedido::query()->where('idPedido', $this->pedidoId)->count();

        $totalCancelados = ItemPedido::query()
            ->where('idPedido', $this->pedidoId)
            ->where('situacao', self::STATUS_CANCELADO)
            ->count();

        if ($totalItens === 0 || $totalItens !== $totalCancelados) {
            return;
        }

        Pedidos::query()->whereKey($this->pedidoId)->update([
            'idSituacao' => self::STATUS_CANCELADO,
            'ResponsavelAtendimento' => $responsavelId,
        ]);

        FasePedido::query()->create([
            'idSituacao' => self::STATUS_CANCELADO,
            'Descricao' => 'Pedido cancelado automaticamente: todos os itens foram cancelados.',
            'id_pedido' => $this->pedidoId,
        ]);
    }

    protected function observacaoDescricao(ItemPedido $item): ?string
    {
        if ($item->validado_por && $item->data_validacao) {
            return "Atendido por: {$item->validadoPorRef?->name} em {$item->data_validacao->format('d/m/Y')}";
        }

        if ($item->cancelado_por && $item->data_cancelado) {
            return "Cancelado por: {$item->canceladoPorRef?->name} em {$item->data_cancelado->format('d/m/Y')}";
        }

        return null;
    }

    protected function getQuery(): Builder
    {
        return ItemPedido::query()
            ->where('idPedido', $this->pedidoId)
            ->with(['descricaoDetalhadaRef', 'materialRef', 'situacaoRef', 'validadoPorRef', 'canceladoPorRef'])
            ->orderBy('situacao');
    }
}
