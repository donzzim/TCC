        {{-- CABEÇALHO COM NAVEGAÇÃO E ABAS --}}
        <div class="flex flex-col gap-4 mb-6">

            {{-- Linha Superior: Botão Criar e Seletor de Mês --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-2">
                <div>

                    @if ($viewMode === 'tasks')
                        <div class="mb-4">
                            {{ $this->taskFiltersForm }}
                        </div>
                    @endif

                    @if ($this->exibirModoCalendario && ($viewMode === 'grid' || $viewMode === 'list'))
                        {{ $this->createEventAction }}
                    @endif

                    @if ($viewMode === 'tasks')
                        @if ($this->isManager)
                            {{ $this->createTaskAction }}
                        @endif

                    @endif
                </div>

                @if ($this->exibirModoCalendario)
                    <div
                        class="flex items-center gap-x-2 bg-gray-50 dark:bg-gray-800 rounded-lg p-1 border dark:border-gray-700">
                        <x-filament::icon-button icon="heroicon-o-chevron-left" wire:click="previousMonth" label="Mês Anterior"
                            color="gray" />
                        <h2
                            class="text-lg font-bold text-center w-40 text-gray-700 dark:text-gray-200 uppercase tracking-wide">
                            {{ $monthName }}</h2>
                        <x-filament::icon-button icon="heroicon-o-chevron-right" wire:click="nextMonth" label="Próximo Mês"
                            color="gray" />
                    </div>
                @endif

                {{-- Controle de Visualização (Abas) --}}
                @if ($this->exibirModoCalendario)
                <div class="flex items-center gap-3">
                    <div
                        x-show="allUsers().length"
                        x-cloak
                        class="flex items-center -space-x-2"
                    >
                        <template x-for="user in allUsers().slice(0, 5)" :key="user.id">
                            <div
                                class="h-9 w-9 overflow-hidden rounded-full border-2 border-white bg-gray-200 text-xs font-bold uppercase text-white shadow-sm ring-1 ring-gray-200 dark:border-gray-900 dark:ring-gray-700"
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
                            x-show="allUsers().length > 5"
                            class="flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-gray-700 text-xs font-semibold text-white shadow-sm dark:border-gray-900"
                            :title="allUsers().slice(5).map((user) => userTooltip(user)).join('\n')"
                            x-text="'+' + (allUsers().length - 5)"
                        ></div>
                    </div>

                    <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                    @if ($this->exibirModoCalendario)
                        <button wire:click="setViewMode('grid')" wire:loading.attr="disabled" wire:target="setViewMode"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-all disabled:opacity-60 disabled:pointer-events-none {{ $viewMode === 'grid' ?'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="h-6 w-6" />
                            Calendário
                        </button>
                        <button wire:click="setViewMode('list')" wire:loading.attr="disabled" wire:target="setViewMode"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-all disabled:opacity-60 disabled:pointer-events-none {{ $viewMode === 'list' ?'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                            <x-filament::icon icon="heroicon-m-clipboard-document-check" class="h-6 w-6" />
                            Lista
                        </button>
                    @endif
                    @if ($this->exibirModoTarefas)
                        <button wire:click="setViewMode('tasks')" wire:loading.attr="disabled" wire:target="setViewMode"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-all disabled:opacity-60 disabled:pointer-events-none {{ $viewMode === 'tasks' ?'bg-white dark:bg-gray-600 shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
                            <x-filament::icon icon="heroicon-m-list-bullet" class="h-6 w-6" />
                            Tarefas
                        </button>
                    @endif
                </div>
                @endif
            </div>
        </div>
        </div>
