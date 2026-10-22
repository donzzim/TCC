<x-filament-widgets::widget>
    <x-filament::card>
        {{-- CABEÇALHO COM NAVEGAÇÃO E ABAS --}}
        <div class="flex flex-col gap-4 mb-6">

            {{-- Linha Superior: Botão Criar e Seletor de Mês --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-2">
                <div>
                    @if($viewMode === 'grid' || $viewMode === 'list')
                    {{ $this->createEventAction }}
                    @endif

                    @if($viewMode === 'tasks')
                    @if($this->isManager)
                    {{ $this->createTaskAction }}
                    @endif
                    @endif
                </div>

                <div
                    class="flex items-center gap-x-2 bg-gray-50 dark:bg-gray-800 rounded-lg p-1 border dark:border-gray-700">
                    <x-filament::icon-button icon="heroicon-o-chevron-left" wire:click="previousMonth"
                        label="Mês Anterior" color="gray" />
                    <h2
                        class="text-lg font-bold text-center w-40 text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                        {{ $monthName }}</h2>
                    <x-filament::icon-button icon="heroicon-o-chevron-right" wire:click="nextMonth" label="Próximo Mês"
                        color="gray" />
                </div>

                {{-- Controle de Visualização (Abas) --}}
                <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                    <button wire:click="setViewMode('grid')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-stone-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        <x-filament::icon icon="heroicon-m-calendar-days" class="h-6 w-6" />
                        Calendário
                    </button>
                    <button wire:click="setViewMode('list')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-stone-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        <x-filament::icon icon="heroicon-m-clipboard-document-check" class="h-6 w-6" />
                        Lista
                    </button>
                    <button wire:click="setViewMode('tasks')"
                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-all {{ $viewMode === 'tasks' ? 'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-stone-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                        <x-filament::icon icon="heroicon-m-list-bullet" class="h-6 w-6" />
                        Tarefas
                    </button>
                </div>
            </div>
        </div>

        {{-- VISÃO GRID (CALENDÁRIO) --}}
        @if($viewMode === 'grid')
        <div class="animate-in fade-in duration-300">
            {{-- Dias da Semana --}}
            <div
                class="grid grid-cols-7 gap-px border-b border-gray-200 dark:border-gray-700 pb-2 mb-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                @foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $day)
                <div>{{ $day }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-1 text-sm">
                {{-- Espaços vazios do início do mês --}}
                @for($i = 0; $i < $firstDayOfWeek; $i++) <div
                    class="min-h-[7rem] bg-gray-50/50 dark:bg-gray-900/30 rounded-md border border-transparent">
            </div>
            @endfor

            {{-- Dias do Mês --}}
            @for($day = 1; $day <= $daysInMonth; $day++) @php $date=$currentMonth->copy()->day($day)->toDateString();
                $dayEvents = collect($events)->filter(fn($e) => $e['start'] <= $date && $e['end']>= $date);
                    $isToday = $date === now()->toDateString();
                    @endphp

                    <div @if($dayEvents->isEmpty())
                        wire:click="mountAction('editEvent', { date: '{{ $date }}' })"
                        class="group relative min-h-32 p-2 border rounded-lg bg-white dark:bg-gray-800
                        dark:border-gray-700 hover:border-primary-400 dark:hover:border-primary-500 cursor-pointer
                        transition-colors {{ $isToday ? 'ring-1 ring-primary-500' : '' }}"
                        @else
                        class="group relative min-h-32 p-2 border rounded-lg bg-white dark:bg-gray-800
                        dark:border-gray-700 {{ $isToday ? 'ring-1 ring-primary-500' : '' }}"
                        @endif
                        >
                        <span
                            class="absolute top-2 right-2 text-xs font-bold {{ $isToday ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ $day }}
                        </span>

                        <div class="mt-5 p-2 flex flex-col gap-1">
                            @foreach($dayEvents as $event)
                            @if(str_contains($event['agendaable_type'], 'CursoAtividades'))
                            {{-- ATIVIDADE --}}
                            <div wire:click="mountAction('viewAtividade', { event_id: {{ $event['id'] }} })"
                                class="mt-2 rounded p-1.5 border-l-2 border-green-600 bg-green-50 cursor-pointer hover:bg-green-800 dark:text-black ">
                                <p class="text-xs font-normal text-green-900 mb-px">{!! $event['title'] !!}</p>
                                <p class="text-xs font-semibold text-green-600">{{ $event['start_time'] }} - {{ $event['end_time'] }}</p>
                            </div>

                            @elseif(str_contains($event['agendaable_type'], 'Cursos'))
                            {{-- CURSO --}}
                            <div wire:click="mountAction('viewCurso', { event_id: {{ $event['id'] }} })"
                                class="mt-2 rounded p-1.5 border-l-2 border-sky-600 bg-sky-50 cursor-pointer hover:bg-sky-800 dark:text-black ">
                                <p class="text-xs font-normal text-sky-900 mb-px">{!! $event['title'] !!}</p>
                                <p class="text-xs font-semibold text-sky-600">{{ $event['start_time'] }} - {{ $event['end_time'] }}</p>
                            </div>

                            @else
                            {{-- AGENDA PADRÃO --}}
                            <div wire:click="mountAction('editEvent', { event_id: {{ $event['id'] }} })"
                                class="mt-2 rounded p-1.5 border-l-2 border-red-600 bg-red-50 cursor-pointer hover:bg-red-800 dark:text-black ">
                                <p class="text-xs font-normal text-red-900 mb-px">{!! strip_tags($event['title']) !!}</p>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endfor
        </div>
        </div>
        @endif

        {{-- VISÃO LISTA DETALHADA (DAY VIEW STYLE) --}}
        @if($viewMode === 'list')
        <div class="animate-in fade-in slide-in-from-bottom-2 duration-300 space-y-6">

            @php
            $hasEvents = false;
            @endphp

            @for($day = 1; $day <= $daysInMonth; $day++) @php $date=$currentMonth->copy()->day($day)->toDateString();
                $carbonDate = $currentMonth->copy()->day($day);
                $dayEvents = collect($events)->filter(fn($e) => $e['start'] <= $date && $e['end']>=
                    $date)->sortBy('start_time');
                    @endphp

                    @if($dayEvents->isNotEmpty())
                    @php $hasEvents = true; @endphp

                    <div class="flex gap-x-4 relative">
                        {{-- Coluna da Data (Sticky no mobile ou fixa na esquerda) --}}
                        <div class="w-16 flex-none flex flex-col items-center text-center pt-2">
                            <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $day }}</span>
                            <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400">{{
                                $carbonDate->translatedFormat('D') }}</span>
                        </div>

                        {{-- Linha Vertical --}}
                        <div class="absolute left-16 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-700"></div>

                        {{-- Lista de Eventos do Dia --}}
                        <div class="flex-auto py-2 space-y-4 pb-8 w-full">
                            @foreach($dayEvents as $event)
                            <div
                                class="group relative flex flex-col sm:flex-row gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:shadow-md hover:border-gray-200 dark:bg-gray-800/50 dark:border-gray-700 dark:hover:bg-gray-800 transition-all">

                                {{-- Horário --}}
                                <div class="sm:w-24 flex-none">
                                    <div
                                        class="flex items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-gray-200">
                                        <x-filament::icon icon="heroicon-m-clock" class="h-4 w-4 text-primary-500" />
                                        {{ $event['start_time'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5 pl-5.5">até {{ $event['end_time'] }}</div>
                                </div>

                                {{-- Detalhes do Evento --}}
                                <div class="flex-auto grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <h3
                                            class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">
                                            {!! $event['title'] !!}
                                        </h3>

                                        @if($event['tipo'])
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mt-2">
                                            {{ $event['tipo'] }}
                                        </span>
                                        @endif

                                        @if(str_contains($event['agendaable_type'], 'CursoAtividades'))
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-green-50 text-green-700 border-2 border-green-700 ml-2">Curso</span>
                                        @elseif(str_contains($event['agendaable_type'], 'Cursos'))
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-sky-50 text-sky-700 border-2 border-sky-700 ml-2">Atividades</span>
                                        @else
                                        <span
                                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-red-50 text-red-700 border-2 border-red-700 ml-2">Feriado</span>
                                        @endif

                                    </div>

                                    {{-- Metadados (Instrutores, Local) --}}
                                    <div
                                        class="text-sm text-gray-600 dark:text-gray-400 space-y-1.5 border-l border-gray-200 dark:border-gray-700 pl-0 sm:pl-4 border-l-0 sm:border-l">
                                        @if(!empty($event['instructors']))
                                        <div class="flex items-start gap-2">
                                            <x-filament::icon icon="heroicon-m-user-group"
                                                class="h-4 w-4 mt-0.5 text-gray-400" />
                                            <span>{{ $event['instructors'] }}</span>
                                        </div>
                                        @endif

                                        @if(!empty($event['location']))
                                        <div class="flex items-start gap-2">
                                            <x-filament::icon icon="heroicon-m-map-pin"
                                                class="h-4 w-4 mt-0.5 text-gray-400" />
                                            <span>{{ $event['location'] }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Botão de ação (Seta) --}}
                                <div class="absolute top-4 right-4 text-gray-300 group-hover:text-primary-500 transition-colors cursor-pointer"
                                    @if(str_contains($event['agendaable_type'], 'CursoAtividades' ))
                                    wire:click="mountAction('viewAtividade', { event_id: {{ $event['id'] }} })"
                                    @elseif(str_contains($event['agendaable_type'], 'Cursos' ))
                                    wire:click="mountAction('viewCurso', { event_id: {{ $event['id'] }} })" @else
                                    wire:click="mountAction('editEvent', { event_id: {{ $event['id'] }} })" @endif>
                                    <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5" />
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @endfor

                    @if(!$hasEvents)
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
        @endif

        {{-- VISÃO DE TAREFAS --}}
        @if($viewMode === 'tasks')
        <div class="animate-in fade-in slide-in-from-bottom-2 duration-300">

            {{-- 1. NOVO: CONTAINER DE FILTROS E CONTROLES --}}
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">

                {{-- Formulário de Filtros (Renderizado pelo PHP) --}}
                <div class="mb-4">
                    {{ $this->taskFiltersForm }}

                    <div class="flex justify-between items-center border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="text-xs text-gray-500">
                            Total de tarefas: <strong>{{ count($taskList) }}</strong>
                        </div>

                        {{-- Botões de Alternância de Visualização (Grid vs List) --}}
                        <div class="flex bg-gray-100 dark:bg-gray-900 rounded-lg p-1 gap-1">
                            <button wire:click="setTaskViewType('grid')"
                                class="p-1.5 rounded {{ $taskViewType === 'grid' ? 'bg-white dark:bg-gray-700 shadow text-primary-600' : 'text-gray-400 hover:text-gray-600' }}"
                                title="Visualização em Cards">
                                <x-filament::icon icon="heroicon-m-squares-2x2" class="h-5 w-5" />
                            </button>
                            <button wire:click="setTaskViewType('list')"
                                class="p-1.5 rounded {{ $taskViewType === 'list' ? 'bg-white dark:bg-gray-700 shadow text-primary-600' : 'text-gray-400 hover:text-gray-600' }}"
                                title="Visualização em Lista">
                                <x-filament::icon icon="heroicon-m-table-cells" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- 2. NOVO: MODO GRID (CARDS) --}}
            @if($taskViewType === 'grid')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($taskList as $task)
                @php
                    // 1. Captura o valor atual
                    $situacao = $task['situacao'];

                    // 2. Define valores padrão (Default - Cinza)
                    $classdiv = 'bg-stone-800 dark:bg-stone-800 border-l-4 border-stone-500 border';
                    $classbtn = 'bg-stone-200 border-stone-700 text-stone-600';

                    // 3. Usa SWITCH para comparação flexível (funciona com texto "1" ou número 1)
                    switch ($situacao) {
                        // Pendente (Amarelo)
                        case config('settings.situacaotarefa.pendente'):
                            $classdiv = 'bg-stone-800 dark:bg-stone-800 border-l-4 border-yellow-500 border';
                            $classbtn = 'bg-yellow-200 border-yellow-700 text-yellow-800 dark:text-yellow-900';
                            break;

                        // Concluída (Verde) - CORRIGIDO: verifique se é 'concluido' ou 'concluida' no seu arquivo config/settings.php
                        case config('settings.situacaotarefa.concluida'): // Fallback caso tenha digitado diferente
                            $classdiv = 'bg-green-800 dark:bg-green-800 border-l-4 border-green-500 border';
                            $classbtn = 'bg-green-200 border-green-700 text-green-800 dark:text-green-900';
                            break;

                        // Cancelada (Vermelho)
                        case config('settings.situacaotarefa.cancelada'):
                            $classdiv = 'bg-red-800 dark:bg-red-800 border-l-4 border-red-500 border';
                            $classbtn = 'bg-red-200 border-red-700 text-red-800 dark:text-red-900';
                            break;
                    }
                @endphp
                <div class="group flex flex-col rounded-xl shadow-sm hover:shadow-md transition-all p-2 {{ $classdiv }}">

                    {{-- Cabeçalho do Card --}}
                    <div class="p-3 border-b border-stone-100 dark:border-stone-700 flex justify-between items-start">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold tracking-wider text-stone-500">{{ $task['tipo_contexto'] }}</span>
                            <span class="text-xs font-medium text-stone-600 dark:text-stone-200 text-wrap max-w-[150px]" title="{{ $task['contexto'] }}">{{ $task['contexto'] }}</span>
                        </div>
                        @if($task['is_assigned'])
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-stone-600 dark:text-stone-300 truncate max-w-[100px] text-right">{{ $task['responsavel'] }}</span>
                            <x-filament::avatar src="{{ $task['responsavel_avatar'] }}" alt="{{ $task['responsavel'] }}" size="xs" class="ring-2 ring-white dark:ring-stone-800 h-6 w-6 flex-shrink-0" />
                            @if($this->isManager)
                            <button wire:click="mountAction('reassignTask', { id: {{ $task['id'] }} })" class="text-stone-400 hover:text-orange-500 transition-colors" title="Reatribuir">
                                <x-filament::icon icon="heroicon-m-arrow-path-rounded-square" class="h-4 w-4" />
                            </button>
                            @endif
                        </div>
                        @elseif($this->isManager)
                        <button wire:click="mountAction('assignTask', { id: {{ $task['id'] }} })" class="text-xs flex items-center gap-1 text-primary-600 bg-primary-50 hover:bg-primary-100 px-2 py-1 rounded transition">
                            <x-filament::icon icon="heroicon-m-user-plus" class="h-3 w-3" /> Atribuir
                        </button>
                        @else
                        <span class="text-xs italic text-stone-400">Não atribuído</span>
                        @endif
                    </div>

                    {{-- Corpo do Card --}}
                    <div class="p-4 flex-auto">
                        <div class="flex justify-between items-start mb-2">
                            {{-- MODIFICAÇÃO: Classe break-words adicionada para evitar sobreposição --}}
                            <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100 break-words line-clamp-3" title="{{ $task['descricao'] }}">
                                {{ $task['descricao'] }}
                            </h3>
                            <div class="flex gap-1 shrink-0">
                                <button wire:click="mountAction('updateTaskStatus', { id: {{ $task['id'] }} })" class="text-stone-400 hover:text-primary-500" title="Atualizar Andamento">
                                    <x-filament::icon icon="heroicon-m-pencil-square" class="h-4 w-4" />
                                </button>
                                @if($this->isManager)
                                <button wire:click="mountAction('editTask', { id: {{ $task['id'] }} })" class="text-stone-400 hover:text-orange-500" title="Editar Completo">
                                    <x-filament::icon icon="heroicon-m-cog-6-tooth" class="h-4 w-4" />
                                </button>
                                @endif
                            </div>
                        </div>

                        {{-- MODIFICAÇÃO: Histórico JSON corrigido para nova estrutura --}}
                        @if(!empty($task['json']) && (isset($task['json']['historico']) || isset($task['json'][0])))
                        <div x-data="{ openHistory: false }" class="mt-3 pt-2 border-t border-stone-100 dark:border-stone-700/50">
                            <button @click="openHistory = !openHistory" class="text-[10px] font-bold text-stone-400 hover:text-primary-500 flex items-center gap-1 transition-colors">
                                <x-filament::icon icon="heroicon-m-clock" class="h-3 w-3" />
                                <span>Histórico ({{ count($task['json']['historico'] ?? $task['json']) }})</span>
                                <x-filament::icon :icon="'heroicon-m-chevron-down'" class="h-3 w-3 transition-transform" x-bind:class="openHistory ? 'rotate-180' : ''" />
                            </button>

                            <div x-show="openHistory" x-collapse style="display: none;" class="mt-2 space-y-2">
                                @php
                                    $logs = $task['json']['historico'] ?? $task['json'];
                                    if (!is_array($logs)) $logs = [];
                                @endphp
                                @foreach(array_reverse($logs) as $log)
                                    @if(is_array($log))
                                    <div class="text-xs bg-stone-50 dark:bg-stone-900/50 p-2 rounded border border-stone-100 dark:border-stone-700">
                                        <div class="flex justify-between text-stone-500 mb-1">
                                            <span class="font-semibold text-[10px]">{{ $log['evento'] ?? 'LOG' }}</span>
                                            <span class="text-[10px]">{{ isset($log['data']) ? \Carbon\Carbon::parse($log['data'])->format('d/m H:i') : '-' }}</span>
                                        </div>
                                        <div class="text-stone-600 dark:text-stone-300">
                                            <span class="font-medium text-red-500">{{ $log['de'] ?? '?' }}</span>
                                            <span class="text-stone-400">&rarr;</span>
                                            <span class="font-medium text-green-600">{{ $log['para'] ?? '?' }}</span>
                                        </div>
                                        @if(!empty($log['motivo']))
                                        <div class="mt-1 text-stone-500 italic border-l-2 border-stone-300 pl-1 ml-0.5">"{{ $log['motivo'] }}"</div>
                                        @endif
                                        <div class="mt-1 text-[9px] text-stone-400 text-right">Por: {{ $log['por'] ?? 'Sistema' }}</div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="mt-2 flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $classbtn }}">
                                {{ $task['status_descricao'] }}
                            </span>
                        </div>
                    </div>

                    {{-- MODIFICAÇÃO: Rodapé com Data Colorida e Lógica de Urgência --}}
                    <div class="p-2 bg-stone-50 dark:bg-stone-800/50 rounded-b-xl border-t border-stone-100 dark:border-stone-700 text-[10px] text-stone-500 flex justify-between items-center">
                        @if(!empty($task['data_prevista']))
                            @php
                                $dtPrevista = \Carbon\Carbon::createFromFormat('d/m/Y', $task['data_prevista']);
                                $hoje = now()->startOfDay();
                                $isConcluida = $task['situacao'] == config('settings.situacaotarefa.concluido');

                                if ($isConcluida) {
                                    $badgeClass = 'bg-green-50 text-green-600 border-green-200 opacity-70';
                                    $iconColor = 'text-green-500';
                                    $diasRestantes = null;
                                } else {
                                    $diasRestantes = $hoje->diffInDays($dtPrevista, false);
                                    $badgeClass = 'bg-stone-100 text-stone-600 border-stone-200';
                                    $iconColor = 'text-stone-400';

                                    if ($diasRestantes <= 1) {
                                        $badgeClass = 'bg-red-50 text-red-700 border-red-200 font-bold';
                                        $iconColor = 'text-red-500';
                                    } elseif ($diasRestantes <= 4) {
                                        $badgeClass = 'bg-yellow-50 text-yellow-700 border-yellow-200 font-medium';
                                        $iconColor = 'text-yellow-500';
                                    }
                                }
                            @endphp

                            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded border {{ $badgeClass }}" title="Data Prevista">
                                <x-filament::icon icon="heroicon-m-calendar" class="h-3 w-3 {{ $iconColor }}" />
                                <span>{{ $task['data_prevista'] }}</span>
                                @if(!$isConcluida)
                                    @if($diasRestantes < 0) <span class="text-[9px] opacity-75">(Atrasado)</span>
                                    @elseif($diasRestantes == 0) <span class="text-[9px] opacity-75">(Hoje)</span>
                                    @endif
                                @else
                                    <x-filament::icon icon="heroicon-m-check" class="h-3 w-3 text-green-500" />
                                @endif
                            </div>
                        @else
                            <div class="flex items-center gap-1 opacity-50">
                                <x-filament::icon icon="heroicon-m-calendar" class="h-3 w-3" />
                                <span>Sem prazo</span>
                            </div>
                        @endif
                        <div class="text-stone-400">ID: #{{ $task['id'] }}</div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-12 text-center">
                    <div class="mx-auto h-12 w-12 text-stone-300"><x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-8 w-8" /></div>
                    <h3 class="mt-2 text-sm font-semibold text-stone-900 dark:text-white">Nenhuma tarefa encontrada</h3>
                </div>
                @endforelse
            </div>
            @endif

            {{-- 3. NOVO: MODO LISTA (TABELA) --}}
            @if($taskViewType === 'list')
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Descrição / Contexto</th>
                            <th class="px-4 py-3">Responsável</th>
                            <th class="px-4 py-3 text-center">Data Prevista</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                        @forelse($taskList as $task)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-4 py-3 font-medium">#{{ $task['id'] }}</td>

                            <td class="px-4 py-3 max-w-md">
                                <div class="text-gray-900 dark:text-white font-medium line-clamp-2" title="{{ $task['descricao'] }}">{{ $task['descricao'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span class="font-bold text-[10px] uppercase">{{ $task['tipo_contexto'] }}:</span> {{ $task['contexto'] }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <x-filament::avatar src="{{ $task['responsavel_avatar'] }}" alt="{{ $task['responsavel'] }}" size="xs" class="h-6 w-6" />
                                    <span class="truncate max-w-[120px]">{{ $task['responsavel'] }}</span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if($task['data_prevista'])
                                    @php
                                        $isAtrasado = \Carbon\Carbon::createFromFormat('d/m/Y', $task['data_prevista'])->isPast() && $task['situacao'] != config('settings.situacaotarefa.concluido');
                                    @endphp
                                    <span class="{{ $isAtrasado ? 'text-red-600 font-bold' : '' }}">{{ $task['data_prevista'] }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                @php
                                    $situacao = $task['situacao'];
                                    $color = 'gray';
                                    switch ($situacao) {
                                        case config('settings.situacaotarefa.pendente'):
                                            $color = 'warning';
                                            break;
                                        case config('settings.situacaotarefa.concluida'):
                                            $color = 'success';
                                            break;
                                        case config('settings.situacaotarefa.cancelada'):
                                            $color = 'danger';
                                            break;
                                        default:
                                            $color = 'gray';
                                            break;
                                    }
                                @endphp
                                <x-filament::badge :color="$color">{{ $task['status_descricao'] }}</x-filament::badge>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="mountAction('updateTaskStatus', { id: {{ $task['id'] }} })" class="text-gray-400 hover:text-primary-600 transition" title="Atualizar">
                                        <x-filament::icon icon="heroicon-m-pencil-square" class="h-5 w-5" />
                                    </button>
                                    @if($this->isManager)
                                    <button wire:click="mountAction('editTask', { id: {{ $task['id'] }} })" class="text-gray-400 hover:text-orange-600 transition" title="Editar">
                                        <x-filament::icon icon="heroicon-m-cog-6-tooth" class="h-5 w-5" />
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">Nenhuma tarefa encontrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif


        </div>
        @endif
    </x-filament::card>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
