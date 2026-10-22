            <div class="animate-in fade-in duration-300">
                {{-- Dias da Semana --}}
                <div
                    class="grid grid-cols-7 gap-px border-b border-gray-200 dark:border-gray-700 pb-2 mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sób'] as $day)
                        <div>{{ $day }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-1 text-sm">
                    {{-- Espaços vazios do início do mês --}}
                    @for ($i = 0; $i < $firstDayOfWeek; $i++)
                        <div
                            class="min-h-[7rem] bg-gray-50/50 dark:bg-gray-900/30 rounded-md border border-transparent">
                        </div>
                    @endfor

                    {{-- Dias do Mês --}}
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = $currentMonth->copy()->day($day)->toDateString();
                            $dayEvents = $calendarEventsByDate[$date] ?? [];
                            $isToday = $date === now()->toDateString();
                        @endphp

                        <div
                            wire:key="calendar-day-{{ $date }}"
                            @if (empty($dayEvents)) wire:click="mountAction('editEvent', { date: '{{ $date }}' })"
                        class="group relative min-h-32 p-2 border rounded-lg bg-white dark:bg-gray-800
                        dark:border-gray-700 hover:border-primary-400 dark:hover:border-primary-500 cursor-pointer
                        transition-colors {{ $isToday ?'ring-1 ring-primary-500' : '' }}"
                        @else
                        class="group relative min-h-32 p-2 border rounded-lg bg-white dark:bg-gray-800
                        dark:border-gray-700 {{ $isToday ?'ring-1 ring-primary-500' : '' }}" @endif>
                            <span
                                class="absolute top-2 right-2 text-xs font-bold {{ $isToday ?'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $day }}
                            </span>

                            <div class="mt-5 p-2 flex flex-col gap-1">
                                @foreach ($dayEvents as $event)
                                    @php $category = $event['category'] ?? 'evento'; @endphp

                                    @if ($category === 'atividade')
                                        {{-- ATIVIDADE --}}
                                        <div wire:click="mountAction('viewEvento', { event_id: {{ $event['id'] }} })"
                                            wire:key="calendar-event-{{ $event['id'] }}-{{ $date }}"
                                            class="mt-2 rounded p-1.5 border-l-2 border-green-600 bg-green-50 cursor-pointer hover:bg-green-100 transition">
                                            <p class="text-xs font-normal text-green-900 mb-px">{!! $event['title'] !!}
                                            </p>
                                            <p class="text-xs font-semibold text-green-600">{{ $event['start_time'] }}
                                                - {{ $event['end_time'] }}</p>
                                        </div>
                                    @elseif($category === 'curso')
                                        {{-- CURSO --}}
                                        <div wire:click="mountAction('viewEvento', { event_id: {{ $event['id'] }} })"
                                            wire:key="calendar-event-{{ $event['id'] }}-{{ $date }}"
                                            class="mt-2 rounded p-1.5 border-l-2 border-sky-600 bg-sky-50 cursor-pointer hover:bg-sky-100 transition">
                                            <p class="text-xs font-normal text-sky-900 mb-px">{!! $event['title'] !!}
                                            </p>
                                            <p class="text-xs font-semibold text-sky-600">{{ $event['start_time'] }} -
                                                {{ $event['end_time'] }}</p>
                                        </div>
                                    @elseif($category === 'acao')
                                        {{-- ACAO --}}
                                        <div wire:click="mountAction('viewEvento', { event_id: {{ $event['id'] }} })"
                                            wire:key="calendar-event-{{ $event['id'] }}-{{ $date }}"
                                            class="mt-2 rounded p-1.5 border-l-2 border-amber-600 bg-amber-50 cursor-pointer hover:bg-amber-100 transition">
                                            <p class="text-xs font-normal text-amber-900 mb-px">{!! $event['title'] !!}
                                            </p>
                                            <p class="text-xs font-semibold text-amber-600">
                                                {{ $event['is_all_day'] ?'Prevista' : $event['start_time'] . ' - ' . $event['end_time'] }}
                                            </p>
                                        </div>
                                    @else
                                        {{-- AGENDA PADRÃO --}}
                                        <div wire:click="mountAction('editEvent', { event_id: {{ $event['id'] }} })"
                                            wire:key="calendar-event-{{ $event['id'] }}-{{ $date }}"
                                            class="mt-2 rounded p-1.5 border-l-2 border-red-600 bg-red-50 cursor-pointer hover:bg-red-100 transition">
                                            <p class="text-xs font-normal text-red-900 mb-px">{!! strip_tags($event['title']) !!}
                                            </p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
