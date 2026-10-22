<div class="space-y-6">
    <form wire:submit="enviar" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
            <span class="text-sm font-medium text-gray-950 dark:text-white">
                Valor total do plano:
                <span class="font-semibold">R$ {{ number_format($this->valorTotal, 2, ',', '.') }}</span>
            </span>

            <div class="flex flex-wrap gap-3">
                <x-filament::button
                    type="button"
                    color="gray"
                    icon="heroicon-m-bookmark"
                    wire:click="salvarRascunho"
                >
                    Salvar rascunho
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    icon="heroicon-m-paper-airplane"
                >
                    Enviar para análise
                </x-filament::button>
            </div>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
