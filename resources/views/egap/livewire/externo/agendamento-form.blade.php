<div x-data="{ tab: 'idaVolta' }">
    <x-filament::section heading="Agendamento de Veículo">
        <x-filament::tabs label="Tipo de solicitação">
            <x-filament::tabs.item
                icon="heroicon-o-arrows-right-left"
                alpine-active="tab === 'idaVolta'"
                x-on:click="tab = 'idaVolta'"
            >
                Veículo - ida e volta
            </x-filament::tabs.item>

            <x-filament::tabs.item
                icon="heroicon-o-arrow-long-right"
                alpine-active="tab === 'ida'"
                x-on:click="tab = 'ida'"
            >
                Veículo - ida
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div x-show="tab === 'idaVolta'" class="fi-section-content-ctn mt-6">
            {{ $this->formIdaVolta }}

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                No campo Justificativa e Informações deverão ser informados, além da justificativa, todos os detalhes importantes para execução do atendimento (ex.: quantidade de passageiros, nome(s) do(s) passageiro(s), tipo de material a ser transportado etc).
            </p>

            <div class="mt-6 flex justify-end">
                <x-filament::button
                    wire:click="enviarSolicitacaoIdaVolta"
                    wire:loading.attr="disabled"
                    wire:target="enviarSolicitacaoIdaVolta"
                >
                    Enviar Solicitação
                </x-filament::button>
            </div>

        </div>

        <div x-show="tab === 'ida'" x-cloak class="fi-section-content-ctn mt-6">
            {{ $this->formIda }}

            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                No campo Justificativa e Informações deverão ser informados, além da justificativa, todos os detalhes importantes para execução do atendimento (ex.: quantidade de passageiros, nome(s) do(s) passageiro(s), tipo de material a ser transportado etc).
            </p>

            <div class="mt-6 flex justify-end">
                <x-filament::button
                    wire:click="enviarSolicitacaoIda"
                    wire:loading.attr="disabled"
                    wire:target="enviarSolicitacaoIda"
                >
                    Enviar Solicitação
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>
</div>
