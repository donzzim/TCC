<div>
    @php($resumoConferencia = $this->resumoConferencia())

    <div class="mb-4 flex flex-wrap items-center justify-end gap-2 print:hidden">
        @if ($resumoConferencia)
            <div class="me-auto flex flex-wrap items-center gap-2 text-sm">
                <x-filament::badge color="warning">Pendentes: {{ $resumoConferencia['pendentes'] }}</x-filament::badge>
                <x-filament::badge color="success">Localizados: {{ $resumoConferencia['localizados'] }}</x-filament::badge>
                <x-filament::badge color="danger">Não localizados: {{ $resumoConferencia['nao_localizados'] }}</x-filament::badge>
                <x-filament::badge color="gray">Divergentes: {{ $resumoConferencia['divergentes'] }}</x-filament::badge>
                @if ($resumoConferencia['prazo'])
                    <x-filament::badge :color="$resumoConferencia['prazo_vencido'] ? 'danger' : 'gray'" icon="heroicon-m-clock">
                        {{ $resumoConferencia['prazo_vencido'] ? 'Prazo vencido em' : 'Prazo:' }} {{ $resumoConferencia['prazo'] }}
                    </x-filament::badge>
                @endif
            </div>

            <x-filament::button
                color="success"
                icon="heroicon-o-qr-code"
                wire:click="mountAction('lerPatrimonio')"
            >
                Ler / buscar patrimônio
            </x-filament::button>
        @endif

        @if ($this->podeFinalizarInventario())
            <x-filament::button
                color="primary"
                icon="heroicon-o-check-circle"
                wire:click="mountAction('finalizarInventario')"
            >
                Finalizar setor
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
