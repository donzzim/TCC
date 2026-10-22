<div class="fi-page-content-wrapper"> {{-- Adiciona um wrapper similar ao Filament --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>

    <div class="flex flex-col items-center justify-center p-4 md:p-8 xl:p-12 bg-sky-50">
        {{-- Header e Logo --}}
        <div class="flex flex-row items-center justify-center mb-2 text-center md:flex-row">
            <div class="mb-4 header-logo-img md:mb-0 md:mr-4">
                <img src="{{ asset('images/logo.png') }}" class="h-16" alt="Logo da Escola da Magistratura" />
            </div>
            <div class="ml-0 text-center md:ml-4 md:text-left">
                <h1 class="text-xl font-bold md:text-2xl lg:text-3xl text-sky-800 dark:text-sky-400">Escola da Magistratura do Estado do Espírito Santo</h1>
            </div>
        </div>

        {{-- Container Principal do Formulário e Detalhes do Curso --}}
        <div class="w-full max-w-lg p-2 bg-white border border-gray-200 shadow-sm md:max-w-2xl lg:max-w-4xl md:p-6 lg:p-8 dark:bg-gray-900 dark:border-gray-700 rounded-xl">

            <div class="max-w-2xl mx-auto p-6 bg-white shadow-md rounded-lg">
                <form wire:submit.prevent="submit">
                    {{ $this->form }}

                    <div class="mt-4">
                        <x-filament::button type="submit">
                            Enviar inscrição
                        </x-filament::button>
                    </div>
                </form>

                @if (session('success'))
                    <div class="mt-4 p-2 bg-green-100 text-green-800 rounded">
                        {{ session('success') }}
                    </div>
                @endif
            </div>
            
        </div>
            
    </div>
</div>
