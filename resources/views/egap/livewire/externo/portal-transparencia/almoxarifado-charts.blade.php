<div class="rounded-2xl border border-gray-200 bg-white shadow dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
            {{ $sectionTitle }}
        </h2>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ $sectionDescription }}
        </p>
    </div>

    <div class="px-6 pt-4">
        <div class="flex flex-col gap-3">
            <label
                for="almoxarifado-indicator"
                class="text-sm font-medium text-gray-700 dark:text-gray-200"
            >
                Gráficos
            </label>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                <div class="flex-1">
                    {{ $this->indicatorForm }}
                </div>

                <div class="lg:w-56">
                    {{ $this->chartTypeForm }}
                </div>

                <x-filament::button
                    wire:click="generateChart"
                    icon="heroicon-s-presentation-chart-bar"
                    aria-label="Gerar gráfico"
                    class="w-full justify-center lg:w-auto"
                >
                    Gerar Gráfico
                </x-filament::button>
            </div>
        </div>
    </div>

    <div class="px-6 pb-6 pt-4">
        @if ($hasGenerated && $currentWidget)
            @livewire(
                $currentWidget,
                ['chartType' => $chartType],
                key('almoxarifado-widget-' . $selectedIndicator . '-' . $chartType . '-' . $widgetRenderKey)
            )
        @else
            <div class="flex h-64 items-center justify-center text-gray-400 dark:text-gray-500">
                Selecione os filtros e clique em "Gerar Gráfico"
            </div>
        @endif
    </div>
</div>
