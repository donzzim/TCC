<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @livewire(\App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\NovoVsRenovacaoChart::class)

        <div class="lg:col-span-2">
            @livewire(\App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\RankingSetoresChart::class)
        </div>

        <div class="lg:col-span-2">
            @livewire(\App\Filament\Egap\Externo\Groups\PlanoContratacao\Widgets\MaioresContratosChart::class)
        </div>
    </div>
</x-filament-panels::page>
