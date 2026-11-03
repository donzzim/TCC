<x-filament-widgets::widget>
    <x-filament::section
        :heading="$this->titulo()"
        :icon="$this->icone()"
        description="Escolha o indicador e o tipo de gráfico e clique em &quot;Gerar gráfico&quot;."
    >
        <div class="flex flex-col gap-3">
            <label
                for="{{ $this->prefixo() }}-indicator"
                class="text-sm font-medium text-gray-700 dark:text-gray-200"
            >
                Gráficos
            </label>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="flex-1">
                    {{ $this->indicatorForm }}
                </div>

                <div class="lg:w-44">
                    {{ $this->chartTypeForm }}
                </div>

                <x-filament::button
                    wire:click="generateChart"
                    icon="heroicon-s-presentation-chart-bar"
                    aria-label="Gerar gráfico"
                    class="w-full justify-center lg:w-auto"
                >
                    Gerar gráfico
                </x-filament::button>
            </div>
        </div>

        <div class="pt-4">
            @if ($currentWidget)
                @livewire(
                    $currentWidget,
                    ['chartType' => $chartType],
                    key($this->prefixo() . '-widget-' . $selectedIndicator . '-' . $chartType . '-' . $widgetRenderKey)
                )
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
