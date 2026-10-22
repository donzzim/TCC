<div x-data x-on:click="$wire.set('rotaAtual', '{{ Route::currentRouteName() }}')">
    {{ $this->helpAction }}

    <x-filament-actions::modals />
</div>
