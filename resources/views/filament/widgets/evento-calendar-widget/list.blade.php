            <div class="animate-in fade-in slide-in-from-bottom-2 duration-300 space-y-6">

                @foreach ($listEventDays as $dayGroup)
                    @php
                        $date = $dayGroup['date'];
                        $day = $dayGroup['day'];
                        $dayEvents = $dayGroup['events'];
                    @endphp

                        <div class="flex gap-x-4 relative" wire:key="list-day-{{ $date }}">
                            {{-- Coluna da Data (Sticky no mobile ou fixa na esquerda) --}}
                            <div class="w-16 flex-none flex flex-col items-center text-center pt-2">
                                <span
                                    class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $day }}</span>
                                <span
                                    class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{ $dayGroup['weekday'] }}</span>
                            </div>

                            {{-- Linha Vertical --}}
                            <div class="absolute left-16 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                            {{-- Lista de Eventos do Dia --}}
                            <div class="flex-auto py-2 space-y-4 pb-8 w-full">
                                @foreach ($dayEvents as $event)
                                    @php
                                        $category = $event['category'] ?? 'evento';
                                        $isCronogramaEvent = in_array($category, ['atividade', 'curso', 'acao'], true);
                                        $actionName = $isCronogramaEvent ? 'editarEventoLista' : 'editEvent';
                                        $dateLabel = $event['list_date_label'] ?? $event['list_primary_label'] ?? \Carbon\Carbon::parse($date)->format('d/m/Y');
                                        $eventPresenceKey = $event['presence_key'] ?? null;
                                        $timeLabel = $event['list_time_label'] ?? $event['list_secondary_label'] ?? ($event['is_all_day'] ? 'Prevista' : (($event['start_time'] ?? '') . (!empty($event['end_time']) ? ' até ' . $event['end_time'] : '')));
                                    @endphp

                                    <div class="space-y-2" wire:key="list-event-{{ $event['id'] }}-{{ $date }}">
                                        <div
                                            role="button"
                                            tabindex="0"
                                            @if ($isCronogramaEvent) x-on:click="localEnter({ key: @js($eventPresenceKey), area: 'formulario' })" @endif
                                            wire:click="mountAction('{{ $actionName }}', { event_id: {{ $event['id'] }} })"
                                            wire:loading.class.delay="opacity-60"
                                            wire:target="mountAction"
                                            class="group relative flex cursor-pointer flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50/50 p-4 pr-11 transition-all hover:border-gray-200 hover:bg-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800/50 dark:hover:bg-gray-800 sm:flex-row">

                                            <div
                                                x-show="usersFor(@js($eventPresenceKey)).length"
                                                x-cloak
                                                class="absolute -top-2 left-3 z-10 flex items-center -space-x-1.5"
                                            >
                                                <template x-for="user in usersFor(@js($eventPresenceKey)).slice(0, 3)" :key="user.id">
                                                    <div
                                                        class="h-5 w-5 overflow-hidden rounded-full border border-white bg-gray-200 text-[8px] font-bold uppercase text-white shadow-sm ring-1 ring-gray-200 dark:border-gray-800 dark:ring-gray-700"
                                                        :title="userTooltip(user)"
                                                    >
                                                        <template x-if="user.avatar_url">
                                                            <img :src="user.avatar_url" :alt="user.name" class="h-full w-full rounded-full object-cover" />
                                                        </template>
                                                        <template x-if="! user.avatar_url">
                                                            <span
                                                                class="flex h-full w-full items-center justify-center rounded-full"
                                                                :style="avatarStyle(user)"
                                                                x-text="user.initials || '?'"
                                                            ></span>
                                                        </template>
                                                    </div>
                                                </template>

                                                <div
                                                    x-show="extraCount(@js($eventPresenceKey)) > 0"
                                                    class="flex h-5 w-5 items-center justify-center rounded-full border border-white bg-gray-700 text-[8px] font-semibold text-white shadow-sm dark:border-gray-800"
                                                    :title="extraTooltip(@js($eventPresenceKey))"
                                                    x-text="'+' + extraCount(@js($eventPresenceKey))"
                                                ></div>
                                            </div>

                                            {{-- Data e horário --}}
                                            <button
                                                type="button"
                                                class="flex-none rounded-lg px-2 py-1 text-left transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:hover:bg-gray-700 sm:w-44"
                                                @if ($isCronogramaEvent) x-on:click="localEnter({ key: @js($eventPresenceKey), area: 'agenda' })" @endif
                                                @if ($isCronogramaEvent) wire:click.stop="mountAction('realocarEventoLista', { event_id: {{ $event['id'] }} })"
                                                @else wire:click.stop="mountAction('editEvent', { event_id: {{ $event['id'] }} })" @endif
                                                wire:loading.attr="disabled"
                                                wire:target="mountAction"
                                            >
                                                <div
                                                    class="flex items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    <x-filament::icon icon="heroicon-m-calendar-days"
                                                        class="h-4 w-4 text-primary-500" />
                                                    {{ $dateLabel }}
                                                </div>
                                                @if (!empty($timeLabel))
                                                    <div class="mt-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                        <x-filament::icon icon="heroicon-m-clock"
                                                            class="h-3.5 w-3.5 text-gray-400" />
                                                        {{ $timeLabel }}
                                                    </div>
                                                @endif
                                            </button>

                                            {{-- Detalhes do Evento --}}
                                            <div class="flex-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div class="min-w-0">
                                                    @if ($isCronogramaEvent)
                                                        <x-cronograma.inline-text
                                                            :event-id="$event['id']"
                                                            field="titulo"
                                                            :value="$event['inline_title'] ?? strip_tags($event['title'])"
                                                            :presence-key="$eventPresenceKey"
                                                            presence-area="titulo"
                                                            display-class="text-base font-semibold text-gray-900 dark:text-white"
                                                        />
                                                    @else
                                                        <h3
                                                            class="text-base font-semibold text-gray-900 transition-colors group-hover:text-primary-600 dark:text-white">
                                                            {!! $event['title'] !!}
                                                        </h3>
                                                    @endif

                                                    @if ($event['tipo'])
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mt-2">
                                                            {{ $event['tipo'] }}
                                                        </span>
                                                    @endif

                                                    @if ($category === 'atividade')
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-green-50 text-green-700 border-2 border-green-700 ml-2">Atividade</span>
                                                    @elseif($category === 'curso')
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-sky-50 text-sky-700 border-2 border-sky-700 ml-2">Curso</span>
                                                    @elseif($category === 'acao')
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-amber-50 text-amber-700 border-2 border-amber-700 ml-2">Ação</span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-red-50 text-red-700 border-2 border-red-700 ml-2">Feriado</span>
                                                    @endif

                                                </div>

                                                {{-- Metadados (Instrutores, Local) --}}
                                                <div
                                                    class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5 border-l border-gray-200 dark:border-gray-700 pl-0 sm:pl-4 border-l-0 sm:border-l">
                                                    @if ($isCronogramaEvent || !empty($event['instructors']))
                                                        <div class="flex items-start gap-2">
                                                            <x-filament::icon icon="heroicon-m-user-group"
                                                                class="h-4 w-4 mt-0.5 text-gray-400" />
                                                            @if ($isCronogramaEvent)
                                                                <x-cronograma.inline-combobox
                                                                    :event-id="$event['id']"
                                                                    field="instrutores"
                                                                    search-kind="instrutores"
                                                                    :items="$event['inline_instructors'] ?? []"
                                                                    :presence-key="$eventPresenceKey"
                                                                    presence-area="instrutores"
                                                                    multiple
                                                                    allow-free
                                                                    placeholder="Adicionar docente"
                                                                    empty-text="Não informado"
                                                                    class="flex-1"
                                                                />
                                                            @else
                                                                <span>{{ $event['instructors'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if ($isCronogramaEvent || !empty($event['location']))
                                                        <div class="flex items-start gap-2">
                                                            <x-filament::icon icon="heroicon-m-map-pin"
                                                                class="h-4 w-4 mt-0.5 text-gray-400" />
                                                            @if ($isCronogramaEvent)
                                                                <x-cronograma.inline-combobox
                                                                    :event-id="$event['id']"
                                                                    field="local_id"
                                                                    search-kind="locais"
                                                                    :items="$event['inline_local_items'] ?? []"
                                                                    :presence-key="$eventPresenceKey"
                                                                    presence-area="local"
                                                                    placeholder="Selecionar local"
                                                                    empty-text="A definir"
                                                                    class="flex-1"
                                                                />
                                                            @else
                                                                <span>{{ $event['location'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if (!empty($event['list_activity_summary']))
                                                        <div class="flex items-start gap-2">
                                                            <x-filament::icon icon="heroicon-m-calendar-days"
                                                                class="h-4 w-4 mt-0.5 text-gray-400" />
                                                            <span>{{ $event['list_activity_summary'] }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <button
                                                type="button"
                                                class="absolute right-3 top-3 rounded-md p-1 text-gray-300 transition-colors hover:bg-gray-100 hover:text-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:hover:bg-gray-700"
                                                @if ($isCronogramaEvent) x-on:click="localEnter({ key: @js($eventPresenceKey), area: 'formulario' })" @endif
                                                wire:click.stop="mountAction('{{ $actionName }}', { event_id: {{ $event['id'] }} })"
                                                wire:loading.attr="disabled"
                                                wire:target="mountAction"
                                                aria-label="Editar"
                                            >
                                                <x-filament::icon icon="heroicon-m-pencil-square" class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                @endforeach

                @if (empty($listEventDays))
                    <div class="text-center py-12">
                        <div class="mx-auto h-12 w-12 text-gray-400">
                            <x-filament::icon icon="heroicon-o-calendar" class="h-full w-full" />
                        </div>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Sem eventos</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Não há eventos agendados para este mês.
                        </p>
                    </div>
                @endif
            </div>
