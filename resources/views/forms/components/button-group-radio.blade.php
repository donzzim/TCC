<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
            state: $wire.entangle('{{ $getStatePath() }}'),
            
            selectOption(value) {
                this.state = value;
            }
        }" class="inline-flex rounded-md shadow-sm" role="group">
        @foreach($getOptions() as $value => $label)
        <button type="button" x-on:click="selectOption('{{ $value }}')" :class="{
                    // Classes para o botão ATIVO
                    'bg-primary-600 text-white dark:bg-primary-500 ring-primary-500 z-10': state == '{{ $value }}',
                    
                    // Classes para o botão INATIVO
                    'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700': state != '{{ $value }}',

                    // Classes de Borda e posicionais
                    'rounded-s-lg': @js($loop->first),
                    'rounded-e-lg': @js($loop->last),
                    '-ms-px': !@js($loop->first),
                }"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 border border-gray-200 focus:z-10 focus:ring-2 focus:ring-primary-700 dark:border-gray-700 dark:text-white dark:focus:ring-primary-500">
            {{-- Adicione um ícone se quiser, ex: --}}
            {{--
            <x-filament::icon icon="heroicon-o-user" class="w-4 h-4 me-2" /> --}}
            {{ $label }}
        </button>
        @endforeach
    </div>
</x-dynamic-component>