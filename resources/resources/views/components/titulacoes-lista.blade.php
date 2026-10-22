<div class="rounded-xl border dark:border-gray-800 border-gray-200 dark:bg-gray-900 bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
        <x-heroicon-o-academic-cap class="w-5 h-5 text-primary-600" />
        Formações Registradas
    </h3>

    <ul class="space-y-3">
        @forelse($this->viewTitulacao ?? [] as $area => $nivel)
            <li class="flex items-center justify-between gap-4 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-800 rounded-lg px-4 py-3">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-700 dark:text-white">{{ $area }}</span>
                    <span class="text-xs dark:text-gray-200 text-gray-500">Nível: {{ $nivel }}</span>
                </div>

                <x-filament::button
                    color="danger"
                    size="sm"
                    wire:click="removerTitulacao('{{ addslashes($area) }}')"
                    icon="heroicon-o-trash"
                >
                    Remover
                </x-filament::button>
            </li>
        @empty
            <li class="text-gray-500 italic text-sm">Nenhuma formação adicionada até o momento.</li>
        @endforelse
    </ul>
</div>
