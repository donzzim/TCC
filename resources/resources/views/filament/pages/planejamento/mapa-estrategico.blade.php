<x-filament-panels::page>

    @if ($planejamento)
    <div class="p-2 md:p-4 lg:p-6 rounded-lg shadow-sm">
        <div class="bg-sky-200 dark:bg-sky-800 shadow-md rounded-xl p-6 mb-8 text-center">
            <h1 class="text-4xl font-bold text-sky-800 dark:text-sky-200 tracking-tight">
                Mapa Estratégico {{ $planejamento->periodo }}
            </h1>
            <p class="mt-2 text-lg text-sky-600 dark:text-sky-400">
                PODER JUDICIÁRIO DO ESTADO DO ESPÍRITO SANTO
            </p>
        </div>

        <div class="bg-stone-100 dark:bg-stone-900 p-4 sm:p-6 lg:p-8 min-h-screen">
            <div class="mx-auto">

                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                    <div class="bg-teal-50 dark:bg-teal-900/50 p-4 rounded-xl">
                        <h2 class="font-bold text-teal-800 dark:text-teal-200">Missão</h2>
                        <p class="mt-1 text-stone-600 dark:text-stone-400">{{ $planejamento->missao }}</p>
                    </div>
                    <div class="bg-sky-50 dark:bg-sky-900/50 p-4 rounded-xl">
                        <h2 class="font-bold text-sky-800 dark:text-sky-200">Visão</h2>
                        <p class="mt-1 text-stone-600 dark:text-stone-400">{{ $planejamento->visao }}</p>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/50 p-4 rounded-xl">
                        <h2 class="font-bold text-amber-800 dark:text-amber-200">Valores</h2>
                        <p class="mt-1 text-stone-600 dark:text-stone-400">{{ $planejamento->valores }}</p>
                    </div>
                </div>


                <div class="space-y-8 mt-8">
                    @foreach ($perspectivas as $nomePerspectiva => $objetivosDaPerspectiva)
                    @php
                    $perspectivaModel = $objetivosDaPerspectiva->first()?->perspectiva;
                    @endphp

                    @if($perspectivaModel)
                    <section class="flex bg-white dark:bg-stone-800 rounded-xl shadow-lg overflow-hidden">
                        <div class="flex-shrink-0 w-16 flex items-center justify-center p-2"
                            style="background-color: {{ $perspectivaModel->cor ?? '#f0f0f0' }};">
                            <h2
                                class="[writing-mode:vertical-lr] rotate-180 text-center font-bold text-stone-800 dark:text-stone-600 uppercase tracking-widest text-shadow-sm">
                                {{ $nomePerspectiva }}
                            </h2>
                        </div>

                        <div class="flex-1 p-4 space-y-2">
                            @foreach ($objetivosDaPerspectiva->groupBy('macrodesafio.nome') as $nomeMacrodesafio =>
                            $objetivosDoMacrodesafio)
                            <div class="macro-block">
                                <h3
                                    class="text-xl font-bold text-stone-700 dark:text-stone-300 border-b-2 dark:border-stone-700 pb-2 mb-2">
                                    {{ $nomeMacrodesafio }}
                                </h3>

                                @foreach ($objetivosDoMacrodesafio as $objetivo)
                                <div
                                    class="relative group p-2 rounded-lg hover:bg-stone-50 dark:hover:bg-white/5 transition-colors">
                                    <p class="font-semibold text-stone-800 dark:text-stone-200 pr-8">
                                        {{ $objetivo->objetivo }}
                                    </p>

                                    <button wire:click='mountAction("editObjetivo", @json(["id" => $objetivo->id]))'
                                        title="Editar Objetivo"
                                        class="absolute top-3 right-3 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                                    </button>

                                    @if ($objetivo->iniciativas->isNotEmpty())
                                    <div class="mt-3 pl-4 border-l-2 border-stone-200 dark:border-stone-700 space-y-2">
                                        @foreach ($objetivo->iniciativas as $iniciativa)
                                        <p class="relative group text-stone-600 dark:text-stone-400">
                                            <span
                                                class="font-mono text-md bg-stone-200 dark:bg-stone-600 px-1 rounded">{{
                                                $iniciativa->codigo }}</span> — {{ $iniciativa->iniciativa }}
                                        </p>

                                        <button
                                            wire:click='mountAction("editIniciativa", @json(["id" => $iniciativa->id]))'
                                            title="Editar Iniciativa"
                                            class="absolute top-9 right-3 text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <x-heroicon-o-pencil-square class="w-5 h-5" />
                                        </button>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </div>
                    </section>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        @push('styles')
        <style>
            .text-shadow-sm {
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            }
        </style>
        @endpush

    </div>
    @else
    <div class="p-2 md:p-4 lg:p-6 rounded-lg shadow-sm">
        <div class="bg-sky-20  0 dark:bg-sky-800 shadow-md rounded-xl p-6 mb-8 text-center">
            <h1 class="text-4xl font-bold text-sky-800 dark:text-sky-200 tracking-tight">
                Mapa Estratégico
            </h1>
            <p class="mt-2 text-lg text-sky-600 dark:text-sky-400">
                Nenhum planejamento encontrado.
            </p>
        </div>
    </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>