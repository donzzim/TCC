<x-filament-panels::page>
    <form wire:submit="create" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <x-filament::button tag="a"
                                :href="\App\Filament\Egap\Interno\Groups\Pedidos\Pages\Requisicao\Pedidos::getUrl()"
                                color="gray" outlined icon="heroicon-m-arrow-left"
                                size="lg">
                Voltar
            </x-filament::button>

            <x-filament::button type="submit" icon="heroicon-m-paper-airplane" size="lg">
                Enviar pedido
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
