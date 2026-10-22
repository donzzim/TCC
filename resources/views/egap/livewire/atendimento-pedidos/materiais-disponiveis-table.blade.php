<div style="height: 100%; min-height: 0; overflow: auto;">
    @if (! $selectedItemPedidoId)
        <div class="flex h-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-400">
            Selecione o pedido que deseja atender
        </div>
    @else
        {{ $this->table }}
    @endif
</div>
