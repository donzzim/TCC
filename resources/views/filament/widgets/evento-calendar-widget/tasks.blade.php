            <div class="animate-in fade-in slide-in-from-bottom-2 duration-300">

                {{-- Filtros Rápidos (Opcional visualmente) --}}
                @if ($this->isManager)
                    <div class="mb-4 flex gap-2">
                        <span class="px-2 py-1 text-xs font-semibold bg-gray-100 rounded text-gray-500">Modo
                            Gestor</span>
                        <span class="text-xs text-gray-400 self-center">Você está vendo todas as tarefas.</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($taskList as $task)
                        @php
                            $class = '';
                            switch ($task['situacao']) {
                                case config('settings.situacaotarefa.pendente'):
                                    $class = 'bg-white border-l-4 border-l-gray-500 border-gray-200';
                                    break;

                                case config('settings.situacaotarefa.concluida'):
                                    $class = 'bg-white border-l-4 border-l-green-500 border-green-200';
                                    break;

                                case config('settings.situacaotarefa.cancelada'):
                                    $class = 'bg-white border-l-4 border-l-red-500 border-red-200';
                                    break;

                                default:
                                    $class = 'bg-white border-l-4 border-l-gray-500 border-gray-200';
                                    break;
                            }
                        @endphp
                        <div
                            class="group flex flex-col dark:bg-gray-800 border rounded-xl shadow-sm hover:shadow-md transition-all p-2 {{ $class }}">

                            {{-- Cabeçalho do Card --}}
                            <div
                                class="p-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-start">
                                <div class="flex flex-col">
                                    {{-- Badge de Contexto --}}
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-gray-500">
                                        {{ $task['tipo_contexto'] }}
                                    </span>
                                    {{-- Nome do Contexto (Processo/Curso) --}}
                                    <span class="text-xs font-medium text-primary-600 truncate max-w-[200px]"
                                        title="{{ $task['contexto'] }}">
                                        {{ $task['contexto'] }}
                                    </span>
                                </div>

                                {{-- Avatar do Responsável ou Botão de Atribuir --}}
                                @if ($task['is_assigned'])
                                    <div class="flex items-center gap-2">
                                        {{-- Avatar (Visualização Normal) --}}
                                        <span
                                            class="text-xs font-medium text-gray-600 dark:text-gray-300 truncate max-w-[100px] text-right">
                                            {{ $task['responsavel'] }}
                                        </span>

                                        {{-- Avatar --}}
                                        <x-filament::avatar src="{{ $task['responsavel_avatar'] }}"
                                            alt="{{ $task['responsavel'] }}" size="xs"
                                            class="ring-2 ring-white dark:ring-gray-800 h-6 w-6 flex-shrink-0" />

                                        {{-- Botão Reatribuir (Apenas Gestor) --}}
                                        @if ($this->isManager)
                                            <button
                                                wire:click="mountAction('reassignTask', { id: {{ $task['id'] }} })"
                                                class="text-gray-400 hover:text-orange-500 transition-colors"
                                                title="Reatribuir para outra pessoa">
                                                <x-filament::icon icon="heroicon-m-arrow-path-rounded-square"
                                                    class="h-4 w-4" />
                                            </button>
                                        @endif
                                    </div>
                                @elseif($this->isManager)
                                    <button wire:click="mountAction('assignTask', { id: {{ $task['id'] }} })"
                                        class="text-xs flex items-center gap-1 text-primary-600 bg-primary-50 hover:bg-primary-100 px-2 py-1 rounded transition">
                                        <x-filament::icon icon="heroicon-m-user-plus" class="h-3 w-3" />
                                        Atribuir
                                    </button>
                                @else
                                    <span class="text-xs italic text-gray-400">Não atribuído</span>
                                @endif
                            </div>

                            {{-- Corpo --}}
                            <div class="p-4 flex-auto">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $task['descricao'] }}
                                    </h3>
                                    {{-- Botão de Edição --}}
                                    <button wire:click="mountAction('updateTaskStatus', { id: {{ $task['id'] }} })"
                                        class="text-gray-400 hover:text-primary-500">
                                        <x-filament::icon icon="heroicon-m-pencil-square" class="h-4 w-4" />
                                    </button>
                                </div>

                                {{-- EXIBIÇÃO DO HISTÓRICO JSON --}}
                                @if (!empty($task['json']))
                                    <div x-data="{ openHistory: false }"
                                        class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/50">
                                        <button @click="openHistory = !openHistory"
                                            class="text-[10px] font-bold text-gray-400 hover:text-primary-500 flex items-center gap-1 transition-colors">
                                            <x-filament::icon icon="heroicon-m-clock" class="h-3 w-3" />
                                            <span>Histórico de Atribuições ({{ count($task['json']) }})</span>
                                            <x-filament::icon :icon="'heroicon-m-chevron-down'" class="h-3 w-3 transition-transform"
                                                x-bind:class="openHistory ?'rotate-180' : ''" />
                                        </button>

                                        <div x-show="openHistory" x-collapse style="display: none;"
                                            class="mt-2 space-y-2">
                                            @foreach (array_reverse($task['json']) as $log)
                                                <div
                                                    class="text-xs bg-gray-50 dark:bg-gray-900/50 p-2 rounded border border-gray-100 dark:border-gray-700">
                                                    <div class="flex justify-between text-gray-500 mb-1">
                                                        <span
                                                            class="font-semibold text-[10px]">{{ $log['evento'] ?? 'LOG' }}</span>
                                                        <span
                                                            class="text-[10px]">{{ isset($log['data']) ?\Carbon\Carbon::parse($log['data'])->format('d/m H:i') : '-' }}</span>
                                                    </div>
                                                    <div class="text-gray-600 dark:text-gray-300">
                                                        <span
                                                            class="font-medium text-red-500">{{ $log['de'] ?? '?' }}</span>
                                                        <span class="text-gray-400">&rarr;</span>
                                                        <span
                                                            class="font-medium text-green-600">{{ $log['para'] ?? '?' }}</span>
                                                    </div>
                                                    @if (!empty($log['motivo']))
                                                        <div
                                                            class="mt-1 text-gray-500 italic border-l-2 border-gray-300 pl-1 ml-0.5">
                                                            "{{ $log['motivo'] }}"
                                                        </div>
                                                    @endif
                                                    <div class="mt-1 text-[9px] text-gray-400 text-right">
                                                        Por: {{ $log['por'] ?? 'Sistema' }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-2 flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                {{ $task['situacao'] ?'bg-green-50 text-green-700 ring-green-600/20' : 'bg-yellow-50 text-yellow-800 ring-yellow-600/20' }}">
                                        {{ $task['status_descricao'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Rodapé --}}
                            <div
                                class="p-2 bg-gray-50 dark:bg-gray-800/50 rounded-b-xl border-t border-gray-100 dark:border-gray-700 text-[10px] text-gray-500 flex justify-between items-center">
                                <div class="flex items-center gap-1">
                                    <x-filament::icon icon="heroicon-m-calendar" class="h-3 w-3" />
                                    Previsto: {{ $task['data_prevista'] }}
                                </div>
                                <div class="text-gray-400">ID: #{{ $task['id'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center">
                            <div class="mx-auto h-12 w-12 text-gray-300">
                                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-8 w-8" />
                            </div>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Nenhuma tarefa
                                encontrada</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $this->isManager
                                    ?'Não há tarefas registradas no sistema.'
                                    : 'Você não possui tarefas
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        pendentes.' }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
