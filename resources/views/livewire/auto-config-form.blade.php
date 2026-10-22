<div>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button
                type="button"
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
            >
                Salvar
            </x-filament::button>
        </div>
    </div>
</div>
