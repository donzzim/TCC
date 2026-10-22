<x-filament-panels::page>
    <div class="space-y-6">
        @livewire(\App\Filament\Egap\Externo\Pages\Widgets\MeusPedidosStats::class)

        @foreach ($this->gruposDeAcesso() as $grupo => $links)
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $grupo }}
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($links as $link)
                        <a href="{{ $link['url'] }}"
                           class="group flex items-start gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                                <x-filament::icon :icon="$link['icon']" class="h-5 w-5"/>
                            </span>

                            <span class="min-w-0">
                                <span
                                    class="block text-sm font-semibold text-gray-950 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">
                                    {{ $link['label'] }}
                                </span>
                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $link['description'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
