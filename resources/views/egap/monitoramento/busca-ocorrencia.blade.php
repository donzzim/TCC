<x-filament-widgets::widget>
    <x-filament::section heading="Buscar código de ocorrência" description="O código de 8 caracteres que aparece na página de erro 500 para o usuário.">
        <form wire:submit="buscar" class="flex flex-wrap items-end gap-2">
            <x-filament::input.wrapper class="w-48">
                <x-filament::input type="text" wire:model="codigo" placeholder="ex.: A1B2C3D4" maxlength="8" class="font-mono uppercase" />
            </x-filament::input.wrapper>
            <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Buscar</x-filament::button>
        </form>

        @if ($buscou)
            <div class="mt-4 text-sm">
                @if ($resultado === null)
                    <p class="text-gray-600 dark:text-gray-300">Ocorrência não encontrada nos logs disponíveis.</p>
                @else
                    @php($c = $resultado['contexto'])
                    <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div><dt class="text-gray-500 dark:text-gray-400">Quando</dt><dd class="text-gray-950 dark:text-white">{{ $resultado['data'] }} · {{ $resultado['arquivo'] }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Exceção</dt><dd class="font-mono text-gray-950 dark:text-white">{{ $c['exception'] ?? '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-gray-500 dark:text-gray-400">Mensagem</dt><dd class="text-gray-950 dark:text-white">{{ $c['message'] ?? $resultado['mensagem'] }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Arquivo</dt><dd class="font-mono break-all text-gray-950 dark:text-white">{{ ($c['file'] ?? '—').(isset($c['line']) ? ':'.$c['line'] : '') }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Requisição</dt><dd class="break-all text-gray-950 dark:text-white">{{ ($c['method'] ?? '') }} {{ $c['url'] ?? '—' }} · usuário {{ $c['user_id'] ?? '—' }}</dd></div>
                    </dl>
                    @if ($resultado['trace'] !== '')
                        <details class="mt-3">
                            <summary class="cursor-pointer text-gray-700 dark:text-gray-200">Stack trace</summary>
                            <pre class="mt-2 max-h-96 overflow-auto rounded bg-gray-50 p-2 text-xs dark:bg-white/5">{{ $resultado['trace'] }}</pre>
                        </details>
                    @endif
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
