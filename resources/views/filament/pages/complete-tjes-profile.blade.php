<x-filament-panels::page>
    <form wire:submit="salvar" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">
                Salvar e continuar
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
