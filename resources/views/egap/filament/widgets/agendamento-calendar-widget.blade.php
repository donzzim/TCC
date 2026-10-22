@php
    $situacaoAgendado = \App\Models\egap\Agendamento\Solicitacao::SITUACAO_AGENDADO;

    $corClasses = [
        'success' => 'border-l-green-600 bg-green-50 hover:bg-green-100 text-green-900',
        'info' => 'border-l-sky-600 bg-sky-50 hover:bg-sky-100 text-sky-900',
        'warning' => 'border-l-amber-600 bg-amber-50 hover:bg-amber-100 text-amber-900',
        'danger' => 'border-l-red-600 bg-red-50 hover:bg-red-100 text-red-900',
        'gray' => 'border-l-gray-500 bg-gray-50 hover:bg-gray-100 text-gray-900',
    ];
@endphp

<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex flex-col gap-4 mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-x-2 bg-gray-50 dark:bg-gray-800 rounded-lg p-1 border dark:border-gray-700">
                    <x-filament::icon-button icon="heroicon-o-chevron-left" wire:click="previousMonth"
                        label="Mês Anterior" color="gray" />
                    <h2 class="text-lg font-bold text-center w-40 text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                        {{ $monthName }}</h2>
                    <x-filament::icon-button icon="heroicon-o-chevron-right" wire:click="nextMonth"
                        label="Próximo Mês" color="gray" />
                </div>

                <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                    <button wire:click="setViewMode('grid')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        <x-filament::icon icon="heroicon-m-calendar-days" class="h-5 w-5" />
                        Calendário
                    </button>
                    <button wire:click="setViewMode('list')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        <x-filament::icon icon="heroicon-m-clipboard-document-check" class="h-5 w-5" />
                        Lista
                    </button>
                </div>
            </div>
        </div>

        @if ($viewMode === 'grid')
            <div class="animate-in fade-in duration-300">
                <div class="grid grid-cols-7 gap-px border-b border-gray-200 dark:border-gray-700 pb-2 mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $day)
                        <div>{{ $day }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-1 text-sm">
                    @for ($i = 0; $i < $firstDayOfWeek; $i++)
                        <div class="min-h-[7rem] bg-gray-50/50 dark:bg-gray-900/30 rounded-md border border-transparent"></div>
                    @endfor

                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = $currentMonth->copy()->day($day)->toDateString();
                            $dayEvents = collect($events)->filter(fn($e) => $e['start'] <= $date && $e['end'] >= $date);
                            $isToday = $date === now()->toDateString();
                        @endphp

                        <div class="group relative min-h-32 p-2 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700 {{ $isToday ? 'ring-1 ring-primary-500' : '' }}">
                            <span class="absolute top-2 right-2 text-xs font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $day }}
                            </span>

                            <div class="mt-5 flex flex-col gap-1">
                                @foreach ($dayEvents as $event)
                                    <div
                                        @if ($event['situacao'] === $situacaoAgendado) wire:click="mountAction('verAgendamento', { event_id: {{ $event['id'] }} })"
                                            class="rounded p-1.5 border-l-2 cursor-pointer transition {{ $corClasses[$event['cor']] ?? $corClasses['gray'] }}"
                                        @else
                                            class="rounded p-1.5 border-l-2 {{ $corClasses[$event['cor']] ?? $corClasses['gray'] }}"
                                        @endif
                                    >
                                        <p class="text-xs font-semibold mb-px">{{ $event['hora_inicio'] }} — {{ $event['trajeto'] }}</p>
                                        <p class="text-[11px] opacity-80">{{ $event['solicitante'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        @endif

        @if ($viewMode === 'list')
            <div class="animate-in fade-in slide-in-from-bottom-2 duration-300 space-y-6">
                @php $hasEvents = false; @endphp

                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = $currentMonth->copy()->day($day)->toDateString();
                        $carbonDate = $currentMonth->copy()->day($day);
                        $dayEvents = collect($events)
                            ->filter(fn($e) => $e['start'] <= $date && $e['end'] >= $date)
                            ->sortBy('hora_inicio');
                    @endphp

                    @if ($dayEvents->isNotEmpty())
                        @php $hasEvents = true; @endphp

                        <div class="flex gap-x-4 relative">
                            <div class="w-16 flex-none flex flex-col items-center text-center pt-2">
                                <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $day }}</span>
                                <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ $carbonDate->translatedFormat('D') }}</span>
                            </div>

                            <div class="absolute left-16 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                            <div class="flex-auto py-2 space-y-4 pb-8 w-full">
                                @foreach ($dayEvents as $event)
                                    <div
                                        @if ($event['situacao'] === $situacaoAgendado) wire:click="mountAction('verAgendamento', { event_id: {{ $event['id'] }} })" @endif
                                        class="group relative flex flex-col sm:flex-row gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50 dark:bg-gray-800/50 dark:border-gray-700 transition-all {{ $event['situacao'] === $situacaoAgendado ? 'cursor-pointer hover:bg-white hover:shadow-md hover:border-gray-200 dark:hover:bg-gray-800' : '' }}">
                                        <div class="sm:w-24 flex-none">
                                            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                <x-filament::icon icon="heroicon-m-clock" class="h-4 w-4 text-primary-500" />
                                                {{ $event['hora_inicio'] }}
                                            </div>
                                            @if ($event['hora_termino'])
                                                <div class="text-xs text-gray-500 mt-0.5 pl-5.5">até {{ $event['hora_termino'] }}</div>
                                            @endif
                                        </div>

                                        <div class="flex-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div>
                                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                                    {{ $event['trajeto'] }}
                                                </h3>
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mt-2 {{ $corClasses[$event['cor']] ?? $corClasses['gray'] }}">
                                                    {{ $event['situacao_descricao'] }}
                                                </span>
                                            </div>

                                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                                <x-filament::icon icon="heroicon-m-user" class="h-4 w-4 mt-0.5 text-gray-400" />
                                                <span>{{ $event['solicitante'] }}</span>
                                            </div>
                                        </div>

                                        @if ($event['situacao'] === $situacaoAgendado)
                                            <div class="absolute top-4 right-4 text-gray-300 group-hover:text-primary-500 transition-colors">
                                                <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endfor

                @if (!$hasEvents)
                    <div class="text-center py-12">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <x-filament::icon icon="heroicon-o-calendar" class="h-full w-full" />
                        </div>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Sem agendamentos</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Não há solicitações agendadas para este mês.</p>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::card>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
