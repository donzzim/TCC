<x-filament-panels::page>
    <form wire:submit="incorporar" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <x-filament::button tag="a"
                                :href="\App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BemMovelResource::getUrl()"
                                color="gray" outlined icon="heroicon-m-arrow-left"
                                size="lg">
                Voltar
            </x-filament::button>

            <x-filament::button type="submit" icon="heroicon-m-check-circle" size="lg">
                Incorporar bens
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
