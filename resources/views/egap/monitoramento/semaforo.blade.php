@php
    $estilo = [
        'verde' => ['cor' => 'success', 'icone' => 'heroicon-o-check-circle', 'titulo' => 'Sistema saudável'],
        'amarelo' => ['cor' => 'warning', 'icone' => 'heroicon-o-exclamation-triangle', 'titulo' => 'Atenção'],
        'vermelho' => ['cor' => 'danger', 'icone' => 'heroicon-o-x-circle', 'titulo' => 'Problema'],
    ][$nivel];
@endphp

<x-filament-widgets::widget wire:poll.5s>
    <x-filament::section>
        <div class="flex items-start gap-3">
            <x-filament::icon :icon="$estilo['icone']" @class([
                'h-8 w-8 shrink-0',
                'text-success-600 dark:text-success-400' => $nivel === 'verde',
                'text-warning-600 dark:text-warning-400' => $nivel === 'amarelo',
                'text-danger-600 dark:text-danger-400' => $nivel === 'vermelho',
            ]) />
            <div class="min-w-0">
                <p class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $estilo['titulo'] }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">· últimos 15 min</span>
                </p>
                @if ($motivos === [])
                    <p class="text-sm text-gray-600 dark:text-gray-300">Tempo de resposta, erros, fila e banco dentro do esperado.</p>
                @else
                    <ul class="mt-1 list-disc ps-5 text-sm text-gray-700 dark:text-gray-200">
                        @foreach ($motivos as $motivo)
                            <li>{{ $motivo }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
