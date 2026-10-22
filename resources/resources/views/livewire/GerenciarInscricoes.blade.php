<div class="fi-modal-content space-y-6 max-h-[calc(100vh-200px)] overflow-y-auto">
    {{-- Cabeçalho com informações do curso --}}
    <div
        class="fi-section bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div
            class="fi-section-header bg-linear-to-r from-primary-50 to-primary-100 dark:from-gray-800 dark:to-gray-700 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="shrink-0">
                        <div
                            class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <h3 class="fi-section-header-heading text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $curso->titulo }}</h3>
                        <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400 mt-1">Carga
                            Horária: {{ $curso->carga_horaria }}h </p>
                        <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400 mt-1">Público:
                            {{ $publicoAlvo }}</p>

                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    @if ($atividades && count($atividades) > 0)
                        <!-- Select de atividades + botão de confirmação (estilizado) -->
                        <div class="flex items-center space-x-3 mr-4">
                            <label for="atividade-select" class="sr-only">Atividade</label>

                            <div class="relative">
                                <select id="atividade-select" wire:model="selectedAtividadeId"
                                    class="block w-64 min-w-[220px] appearance-none pr-10 pl-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500/25">
                                    <option value="">{{ $pai->titulo }}</option>
                                    @foreach ($atividades as $id => $titulo)
                                        <option value="{{ $id }}">{{ $titulo }}</option>
                                    @endforeach
                                </select>

                                <!-- chevron icon -->
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            <button type="button" wire:click="switchAtividades"
                                class="fi-btn fi-btn-size-sm inline-flex items-center justify-center font-semibold rounded-lg px-3 py-2 text-sm bg-primary-600 text-white shadow-sm hover:bg-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                Confirmar
                            </button>
                        </div>
                    @endif

                    <div
                        class="fi-stats-card bg-white dark:bg-gray-800 rounded-lg px-4 py-3 shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-3">
                            <div
                                class="fi-stats-card bg-white dark:bg-gray-800 rounded-lg px-4 py-3 shadow-sm border border-gray-200 dark:border-gray-700">
                                <div
                                    class="fi-stats-card-value text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ $vagasPreenchidas }}/{{ $capacidade }}</div>
                                <div
                                    class="fi-stats-card-label text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    vagas preenchidas</div>
                            </div>
                            <div
                                class="fi-stats-card bg-white dark:bg-gray-800 rounded-lg px-4 py-3 shadow-sm border border-gray-200 dark:border-gray-700">
                                @if ($vagasDisponiveis > 0)
                                    <div
                                        class="fi-stats-card-value text-2xl font-bold text-success-600 dark:text-success-400">
                                        {{ $vagasDisponiveis }}</div>
                                    <div
                                        class="fi-stats-card-label text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                        vagas restantes</div>
                                @else
                                    <div
                                        class="fi-stats-card-value text-2xl font-bold text-danger-600 dark:text-danger-400">
                                        {{ $vagasDisponiveis * -1 }}</div>
                                    <div
                                        class="fi-stats-card-label text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                        vagas excedentes</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if ($vagasDisponiveis <= 0)
                    <div class="fi-section-content p-6">
                        <div
                            class="fi-banner fi-color-danger bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="shrink-0">
                                    <svg class="w-5 h-5 text-danger-600 dark:text-danger-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <span class="fi-banner-text text-danger-800 dark:text-danger-200 font-medium">Não há
                                        mais
                                        vagas disponíveis para este curso</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Contador de selecionados --}}
            @if (count($selectedUsers) > 0)
                <div
                    class="fi-section bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="fi-section-content p-4">
                        <div
                            class="fi-banner fi-color-success bg-success-50 dark:bg-success-950 border border-success-200 dark:border-success-800 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="shrink-0">
                                        <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <span
                                            class="fi-banner-text text-success-800 dark:text-success-200 font-medium">{{ count($selectedUsers) }}
                                            usuários selecionados</span>
                                    </div>
                                </div>
                                <button type="button" wire:click.prevent="desmarcarTodos"
                                    class="fi-link fi-size-sm fi-color-success text-success-600 dark:text-success-400 hover:text-success-500 dark:hover:text-success-300 font-medium transition-colors">
                                    Limpar seleção
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Grupos de prioridade --}}
            <div class="space-y-4">
                @forelse($gruposPrioridade as $grupo)
                    <div class="fi-section bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden {{ $grupo['cor'] }}"
                        x-data="{ expanded: true }">
                        <div
                            class="fi-section-header px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <button type="button" @click="expanded = !expanded"
                                        class="fi-collapsible-trigger flex items-center justify-center w-8 h-8 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': !expanded }" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <h4
                                        class="fi-section-header-heading text-lg font-semibold ml-4 text-gray-950 dark:text-white">
                                        {{ $grupo['nome'] }} </h4>
                                    <span
                                        class="fi-badge fi-size-sm inline-flex items-center px-2.5 py-0.5 rounded-full text-xs ml-4 font-medium {{ $grupo['badge_color'] }}">
                                        {{ $grupo['usuarios']->count() }} candidatos
                                    </span>
                                </div>
                                <button type="button" wire:click.prevent="selecionarMaximo('{{ $grupo['key'] }}')"
                                    @if ($vagasDisponiveis <= 0) disabled @endif
                                    class="fi-btn fi-btn-size-sm fi-color-primary inline-flex items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-size-sm gap-1.5 px-3 py-2 text-sm bg-primary-600 text-white shadow-sm hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50 fi-ac-action fi-ac-btn-action disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none">
                                    <svg class="fi-btn-icon w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span class="fi-btn-label">Selecionar Máximo</span>
                                </button>
                            </div>
                        </div>

                        <div class="fi-section-content overflow-hidden transition-all duration-300 ease-in-out"
                            x-show="expanded" x-collapse>
                            <div class="p-6 w-full">
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 w-full">
                                    @foreach ($grupo['usuarios'] as $aluno)
                                        @php
                                            $isSelected = in_array($aluno, $selectedUsers);
                                            $extrapolado = $vagasDisponiveis <= 0;
                                        @endphp
                                        <div
                                            class="fi-checkbox-list-item group flex items-center gap-x-3 rounded-lg border
                                        @if ($isSelected && $extrapolado) border-danger-600 ring-2 ring-danger-600 bg-danger-50 dark:bg-danger-950
                                        @elseif($isSelected)
                                            ring-2 ring-primary-600 dark:ring-primary-500 border-primary-600 dark:border-primary-500 bg-primary-50 dark:bg-primary-950
                                        @else
                                            border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 @endif
                                        bg-white dark:bg-gray-900 p-4 shadow-sm transition-all hover:shadow-md w-full">
                                            <div class="fi-checkbox-input">
                                                <input type="checkbox" wire:click="toggleUser({{ $aluno->id }})"
                                                    {{ $isSelected ? 'checked' : '' }}
                                                    class="fi-checkbox-input rounded border-none bg-white text-primary-600 shadow-sm outline-none ring-1 ring-gray-950/10 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-primary-600/50 disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500/50 dark:disabled:bg-transparent dark:disabled:ring-white/10 dark:disabled:checked:ring-white/20">
                                            </div>
                                            <div class="fi-checkbox-label flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-950 dark:text-white truncate">
                                                    {{ $aluno->name }}
                                                    @if (!empty($aluno->perfil->pcd))
                                                        @php
                                                            $pcd = [];
                                                            foreach ($aluno->perfil->pcd as $deficiencia) {
                                                                $tipoId = $deficiencia['tipo'] ?? null;
                                                                if ($tipoId) {
                                                                    $tipo = \App\Models\Escola\TipoDeficiencia::find(
                                                                        $tipoId,
                                                                    )->codigo;
                                                                }
                                                                $item = $tipo ?? null;
                                                                $necessidadeRaw =
                                                                    $deficiencia['descricao_necessidade'] ?? null;
                                                                if ($necessidadeRaw) {
                                                                    $necessidade = $necessidadeRaw
                                                                        ? trim(
                                                                            strip_tags(
                                                                                html_entity_decode(
                                                                                    (string) $necessidadeRaw,
                                                                                ),
                                                                            ),
                                                                        )
                                                                        : null;
                                                                    $item .= ' - ' . $necessidade;
                                                                }
                                                                // dd($item);
                                                                $pcd[] = $item;
                                                            }
                                                        @endphp

                                                        @if (!empty($pcd))
                                                            <span x-data="{ open: false }" @mouseenter="open = true"
                                                                @mouseleave="open = false"
                                                                class="relative inline-flex">
                                                                <span
                                                                    class="fi-badge fi-size-xs inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-yellow-100 text-yellow-800 ring-yellow-300 dark:bg-yellow-900 dark:text-yellow-600"
                                                                    title="{{ !empty($pcd) ? implode(', ', $pcd) : '' }}">
                                                                    PCD
                                                                </span>

                                                                <div x-cloak x-show="open" x-transition
                                                                    class="absolute left-1/2 -translate-x-1/2 mt-2 w-56 z-50 rounded-md border border-gray-200 bg-white dark:bg-gray-800 shadow-lg p-2 text-sm text-gray-700 dark:text-gray-200">
                                                                    <div
                                                                        class="font-semibold mb-1 text-xs text-gray-600 dark:text-gray-300">
                                                                        Deficiências</div>
                                                                    <ul class="list-inside list-disc space-y-1">
                                                                        @foreach ($pcd as $def)
                                                                            <li class="truncate">{{ $def }}
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </span>
                                                        @endif
                                                    @endif
                                                </p>
                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span
                                                        class="fi-badge fi-size-xs inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                                        {{ $aluno->email }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span
                                                        class="fi-badge fi-size-xs inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                                        {{ $aluno->perfil->cargo ?? 'Sem cargo vinculado' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span
                                                        class="fi-badge fi-size-xs inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                                        {{ $aluno->setorPrincipal()->first()->setor ?? 'Sem Setor vinculado' }}
                                                    </span>
                                                </div>

                                                @if ($aluno->alunoCurso->where('curso_id', $curso->id)->first()->data_inscricao)
                                                    <div class="flex items-center space-x-2 mt-1">
                                                        <span
                                                            class="fi-badge fi-size-xs inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                                            {{-- Formatar data e hora da inscricao --}}
                                                            Inscrito em:
                                                            {{ $aluno->alunoCurso->where('curso_id', $curso->id)->first()->data_inscricao }}
                                                        </span>
                                                    </div>
                                                @endif

                                                <div class="flex items-center space-x-2 mt-1">
                                                    <span
                                                        class="fi-badge fi-size-xs inline-flex items-center gap-x-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">

                                                        Status:
                                                        {{ $aluno->alunoCurso->where('curso_id', $curso->id)->first()->situacaoinscricao->descricao }}
                                                    </span>
                                                </div>

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                @empty
                    <div
                        class="fi-section bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div class="fi-section-content text-center py-12">
                            <div class="fi-empty-state mx-auto max-w-lg">
                                <div class="fi-empty-state-icon-ctn mb-4 flex justify-center">
                                    <div
                                        class="fi-empty-state-icon flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                        <svg class="fi-empty-state-icon-svg h-6 w-6 text-gray-500 dark:text-gray-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <h3
                                    class="fi-empty-state-heading text-base font-semibold text-gray-950 dark:text-white">
                                    Nenhuma pré-inscrição encontrada</h3>
                                <p class="fi-empty-state-description text-sm text-gray-500 dark:text-gray-400 mt-1">Não
                                    há
                                    usuários pré-inscritos neste curso.</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Rodapé com ações --}}
            <div
                class="fi-modal-footer flex flex-col-reverse items-center justify-between gap-3 pt-6 border-t border-gray-200 dark:border-gray-700 sm:flex-row">
                <div class="fi-modal-footer-info text-sm text-gray-500 dark:text-gray-400">
                    @if (count($selectedUsers) > 0)
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ count($selectedUsers) }}</span>
                        de <span
                            class="font-medium text-gray-700 dark:text-gray-300">{{ $capacidade - $vagasPreenchidas }}</span>
                        vagas serão preenchidas
                    @else
                        Selecione os usuários para confirmar as inscrições
                    @endif
                </div>

                <div class="fi-modal-footer-actions flex items-center gap-3">
                    <button wire:click="confirmarInscricoes" @if (count($selectedUsers) === 0) disabled @endif
                        class="fi-btn fi-btn-color-success fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-150 focus-visible:ring-2 rounded-lg fi-size-md gap-2 px-6 py-2 text-base bg-linear-to-r from-success-500 via-success-600 to-success-700 text-black dark:text-white shadow-lg hover:scale-105 hover:shadow-xl focus-visible:ring-success-500/50 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none border-2 border-success-700">

                        <span class="fi-btn-label">Confirmar {{ count($selectedUsers) }} Inscrições</span>
                    </button>
                </div>
            </div>
