@php
    $corClasses = [
        'success' => 'border-l-green-600 bg-green-50 hover:bg-green-100 text-green-900 dark:bg-green-900/30 dark:text-green-200',
        'info' => 'border-l-sky-600 bg-sky-50 hover:bg-sky-100 text-sky-900 dark:bg-sky-900/30 dark:text-sky-200',
        'danger' => 'border-l-red-600 bg-red-50 hover:bg-red-100 text-red-900 dark:bg-red-900/30 dark:text-red-200',
        'gray' => 'border-l-gray-500 bg-gray-50 hover:bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-200',
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-x-2 rounded-lg border p-1 dark:border-gray-700">
                <x-filament::icon-button icon="heroicon-o-chevron-left" wire:click="previousMonth" label="Mês anterior" color="gray" />
                <h2 class="w-44 text-center text-lg font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">
                    {{ $monthName }}
                </h2>
                <x-filament::icon-button icon="heroicon-o-chevron-right" wire:click="nextMonth" label="Próximo mês" color="gray" />
            </div>

            <div class="flex flex-wrap gap-3 text-xs">
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-sky-500"></span> Contratação prevista</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-green-500"></span> Contratação concluída</span>
                <span class="inline-flex items-center gap-1"><span class="h-3 w-3 rounded-sm bg-red-500"></span> Prevista em atraso</span>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-px border-b border-gray-200 pb-2 mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
            @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $day)
                <div>{{ $day }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-1 text-sm">
            @for ($i = 0; $i < $firstDayOfWeek; $i++)
                <div class="min-h-32 rounded-md border border-transparent bg-gray-50/50 dark:bg-gray-900/30"></div>
            @endfor

            @for ($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $date = $currentMonth->copy()->day($day)->toDateString();
                    $dayEvents = collect($events)->filter(fn ($e) => $e['start'] === $date);
                    $isToday = $date === now()->toDateString();
                @endphp

                <div class="relative min-h-32 rounded-lg border bg-white p-2 dark:border-gray-700 dark:bg-gray-800 {{ $isToday ? 'ring-1 ring-primary-500' : '' }}">
                    <span class="absolute right-2 top-2 text-xs font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $day }}
                    </span>

                    <div class="mt-5 flex flex-col gap-1">
                        @foreach ($dayEvents as $event)
                            <button
                                type="button"
                                wire:click="mountAction('verEvento', { event_id: {{ $event['id'] }} })"
                                class="w-full rounded border-l-2 p-1.5 text-left transition {{ $corClasses[$event['cor']] ?? $corClasses['gray'] }}"
                            >
                                <p class="text-xs font-semibold {{ $event['texto_atrasado'] ? 'text-red-600 dark:text-red-400' : '' }}">
                                    {{ \Illuminate\Support\Str::limit($event['titulo'], 40) }}
                                </p>
                                <p class="text-[11px] opacity-80">{{ $event['setor'] }}</p>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endfor
        </div>

        <x-filament-actions::modals />
    </x-filament::card>
</x-filament-widgets::widget>
