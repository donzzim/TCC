<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-filament::section heading="Aplicação">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">PHP</dt><dd class="font-mono">{{ $s['php'] }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Laravel</dt><dd class="font-mono">{{ $s['laravel'] }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Filament</dt><dd class="font-mono">{{ $s['filament'] }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Livewire</dt><dd class="font-mono">{{ $s['livewire'] }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">MySQL</dt><dd class="font-mono">{{ $s['mysql'] ?? 'indisponível' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Branch</dt><dd class="font-mono break-all">{{ $s['branch'] ?? '—' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Commit</dt><dd class="font-mono">{{ $s['commit'] ? substr($s['commit'], 0, 10) : '—' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">memory_limit</dt><dd class="font-mono">{{ $s['memory_limit'] }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">max_execution_time</dt><dd class="font-mono">{{ $s['max_execution_time'] }} s</dd>
            </dl>
        </x-filament::section>

        <x-filament::section heading="Recursos">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">Disco livre</dt>
                <dd>{{ $gb($s['disco_livre_bytes']) }} de {{ $gb($s['disco_total_bytes']) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">storage/logs</dt><dd>{{ $mb($s['tamanho_logs_bytes']) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">Métricas (SQLite + buffer)</dt><dd>{{ $mb($s['tamanho_app_storage_bytes']) }}</dd>
                @if ($s['opcache'] === null)
                    <dt class="text-gray-500 dark:text-gray-400">OPcache</dt><dd>extensão não carregada</dd>
                @else
                    <dt class="text-gray-500 dark:text-gray-400">OPcache</dt><dd>{{ $s['opcache']['ativo'] ? 'ativo' : 'desligado' }}</dd>
                    <dt class="text-gray-500 dark:text-gray-400">OPcache: memória</dt>
                    <dd>{{ number_format($s['opcache']['memoria_usada_mb'], 1, ',', '.') }} MB usados · {{ number_format($s['opcache']['memoria_livre_mb'], 1, ',', '.') }} MB livres</dd>
                    <dt class="text-gray-500 dark:text-gray-400">OPcache: acerto</dt>
                    <dd>{{ number_format($s['opcache']['taxa_acerto'], 2, ',', '.') }}% · {{ number_format($s['opcache']['scripts'], 0, ',', '.') }} scripts</dd>
                @endif
            </dl>
        </x-filament::section>

        <x-filament::section heading="Checklist de configuração" class="lg:col-span-2">
            <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                @foreach ($checklist as $item)
                    <li class="flex items-start gap-3 py-2">
                        <x-filament::icon
                            :icon="$item['ok'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle'"
                            @class([
                                'mt-0.5 h-5 w-5 shrink-0',
                                'text-success-600 dark:text-success-400' => $item['ok'],
                                'text-warning-600 dark:text-warning-400' => ! $item['ok'],
                            ])
                        />
                        <div>
                            <p class="font-medium text-gray-950 dark:text-white">
                                {{ $item['titulo'] }}
                                <span class="sr-only">{{ $item['ok'] ? '(ok)' : '(atenção)' }}</span>
                            </p>
                            <p class="text-gray-600 dark:text-gray-300">{{ $item['detalhe'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
