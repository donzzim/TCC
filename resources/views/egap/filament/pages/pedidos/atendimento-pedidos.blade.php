<x-filament-panels::page>
    <x-grid style="align-items: stretch;">
        <section style="height: 720px; overflow: hidden; display: flex; flex-direction: column;"
                 class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div style="flex: 1; min-height: 0; overflow: hidden; padding: 1rem;">
                @livewire(\App\Filament\Egap\Interno\Groups\Pedidos\Components\PedidosEmAbertoTable::class, [], key('pedidos-em-aberto-table'))
            </div>
        </section>

        <section style="height: 720px; overflow: hidden; display: flex; flex-direction: column;"
                 class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div style="flex: 1; min-height: 0; overflow: hidden; padding: 1rem;">
                @livewire(\App\Filament\Egap\Interno\Groups\Pedidos\Components\MateriaisDisponiveisTable::class, [], key('materiais-disponiveis-table'))
            </div>
        </section>
    </x-grid>

    <section class="mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="p-4 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Atendimento do pedido
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Revise a seleção antes de concluir o atendimento.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Pedido selecionado</h3>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Pedido</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedPedidoId ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Item</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $selectedItemPedidoId ?: '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Protocolo</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $protocolo ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Solicitante</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $solicitante ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Destino</dt>
                            <dd class="font-medium text-right text-gray-900 dark:text-white">{{ $destino ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Material</dt>
                            <dd class="font-medium text-right text-gray-900 dark:text-white">
                                {{ $materialPedido ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Situação</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $situacaoPedido ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Quantidades</h3>

                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Solicitada</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $quantidadeSolicitada }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Validada</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $quantidadeValidada }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Atendida</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $quantidadeAtendida }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Pendente</dt>
                            <dd class="font-semibold text-warning-600 dark:text-warning-400">{{ $quantidadePendente }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-gray-400">Selecionados</dt>
                            <dd class="font-semibold text-primary-600 dark:text-primary-400">
                                {{ $this->quantidadeSelecionada }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Validação</h3>

                    <div class="space-y-3 text-sm">
                        @if (!$selectedItemPedidoId)
                            <div
                                class="rounded-lg bg-gray-100 px-3 py-2 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                Selecione um pedido para iniciar.
                            </div>
                        @elseif ($this->quantidadeSelecionada === 0)
                            <div
                                class="rounded-lg bg-warning-50 px-3 py-2 text-warning-700 dark:bg-warning-900/20 dark:text-warning-300">
                                Nenhum patrimônio selecionado.
                            </div>
                        @elseif ($this->quantidadeSelecionada < $quantidadePendente)
                            <div
                                class="rounded-lg bg-warning-50 px-3 py-2 text-warning-700 dark:bg-warning-900/20 dark:text-warning-300">
                                Ainda faltam {{ $quantidadePendente - $this->quantidadeSelecionada }} patrimônio(s).
                            </div>
                        @elseif ($this->quantidadeSelecionada === $quantidadePendente)
                            <div
                                class="rounded-lg bg-success-50 px-3 py-2 text-success-700 dark:bg-success-900/20 dark:text-success-300">
                                Quantidade correta para atendimento.
                            </div>
                        @else
                            <div
                                class="rounded-lg bg-danger-50 px-3 py-2 text-danger-700 dark:bg-danger-900/20 dark:text-danger-300">
                                Há patrimônios além do permitido.
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-3 pt-2">
                            <x-filament::button color="success" wire:click="atenderPedido"
                                                :disabled="!$this->podeAtender">
                                Atender pedido
                            </x-filament::button>

                            <x-filament::button color="gray" outlined wire:click="limparSelecao">
                                Limpar seleção
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>

            @if (count($selectedPatrimonios))
                <div class="mt-4 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                        Patrimônios selecionados
                    </h3>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($selectedPatrimonios as $patrimonioId)
                            <span
                                class="inline-flex items-center rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                #{{ $patrimonioId }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-filament-panels::page>
