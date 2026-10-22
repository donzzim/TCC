<div class="space-y-6">
    <x-filament::section>
        <x-slot name="heading">Carrinho</x-slot>

        @if (empty($this->carrinho))
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum material adicionado ainda.</p>
        @else
            <div class="space-y-3">
                @foreach ($this->carrinho as $index => $item)
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-950 dark:text-white truncate">{{ $item['descricao'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $item['quantidade'] }} x R$ {{ number_format($item['preco_unitario'], 2, ',', '.') }}
                            </p>
                            @isset ($item['tipo_atendimento'])
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @if ($item['tipo_atendimento'] === 'substituicao')
                                        Substituição do patrimônio {{ $item['patrimonio_substituido'] }}
                                    @else
                                        Adição
                                    @endif
                                </p>
                            @endisset
                        </div>

                        <x-filament::icon-button
                            icon="heroicon-o-trash"
                            color="danger"
                            label="Remover"
                            wire:click="removerItem({{ $index }})"
                        />
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex items-center justify-between pt-3">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Total</span>
                <span class="text-sm font-semibold text-gray-950 dark:text-white">
                    R$ {{ number_format($this->subtotalCarrinho, 2, ',', '.') }}
                </span>
            </div>

            <div class="mt-3">
                <x-filament::button
                    color="gray"
                    outlined
                    size="sm"
                    icon="heroicon-m-trash"
                    wire:click="limparCarrinho"
                >
                    Limpar carrinho
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Dados do pedido</x-slot>

        <form wire:submit="enviarPedido" class="space-y-6">
            {{ $this->form }}

            <x-filament::button
                type="submit"
                icon="heroicon-m-paper-airplane"
                class="w-full"
            >
                Enviar Pedido
            </x-filament::button>
        </form>
    </x-filament::section>
</div>
