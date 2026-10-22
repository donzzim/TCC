<div>
    <div class="mb-4 flex flex-wrap items-center justify-end gap-2 print:hidden">
        @if ($this->podeFinalizarInventario())
            <x-filament::button
                color="primary"
                icon="heroicon-o-check-circle"
                wire:click="mountAction('finalizarInventario')"
            >
                Finalizar Inventário
            </x-filament::button>
        @endif

        <x-filament::button
            color="info"
            icon="heroicon-o-plus-circle"
            wire:click="mountAction('incluirBem')"
        >
            Incluir Bem
        </x-filament::button>

        <x-filament::button
            tag="a"
            color="gray"
            icon="heroicon-o-printer"
            :href="route('patrimonio.bens-no-setor.imprimir')"
            target="_blank"
        >
            Imprimir
        </x-filament::button>
    </div>

    <div class="pb-4">
        {{ $this->table }}
    </div>
</div>
