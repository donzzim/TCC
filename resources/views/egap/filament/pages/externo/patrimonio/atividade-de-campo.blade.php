<x-filament-panels::page>
    <div
        class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="w-full max-w-xs">
                <label for="inventarioId"
                       class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">
                    Inventário
                </label>
                <x-filament::input.wrapper class="mt-1">
                    <x-filament::input.select id="inventarioId" wire:model.live="inventarioId">
                        <option value="">Selecionar Inventário</option>
                        @foreach ($this->inventarios() as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <x-filament::button color="gray" icon="heroicon-o-printer" onclick="window.print()">
                Imprimir
            </x-filament::button>
        </div>

        <div class="fi-tabs mt-4 flex flex-wrap gap-2 print:hidden mt-3">
            <x-filament::button :color="$secao === 'resumo' ? 'primary' : 'gray'" wire:click="selecionarSecao('resumo')"
                                icon="heroicon-o-chart-bar">
                Resumo por Setor
            </x-filament::button>

            <x-filament::button :color="$secao === 'nao-localizados' ? 'primary' : 'gray'"
                                wire:click="selecionarSecao('nao-localizados')"
                                icon="heroicon-o-hand-thumb-down">
                Não Localizados
            </x-filament::button>

            <x-filament::button :color="$secao === 'em-transferencia' ? 'primary' : 'gray'"
                                wire:click="selecionarSecao('em-transferencia')"
                                icon="heroicon-o-truck">
                Em Transferência
            </x-filament::button>
        </div>
    </div>

    <div class="mt-4">
        @if ($secao === 'resumo')
            @livewire(\App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoResumoTable::class, ['inventarioId' => $inventarioId])
        @elseif ($secao === 'nao-localizados')
            @livewire(\App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoNaoLocalizadosTable::class)
        @elseif ($secao === 'em-transferencia')
            @livewire(\App\Filament\Egap\Externo\Groups\Patrimonio\Components\AtividadeDeCampoEmTransferenciaTable::class)
        @endif
    </div>
</x-filament-panels::page>
