<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @forelse($alunos as $aluno)
    {{-- Card do Aluno com escopo AlpineJS --}}
    <div x-data="{
            editandoId: null,
            justificativa: '',
            frequenciasJustificadas: [], // Armazena IDs que foram justificados para trocar a cor visualmente

            abrirForm(id) {
                this.editandoId = id;
                this.justificativa = '';
                // Foca no textarea após abrir (pequeno delay para renderizar)
                setTimeout(() => $refs.inputJustificativa.focus(), 50);
            },

            cancelar() {
                this.editandoId = null;
                this.justificativa = '';
            },

            salvar() {
                if (!this.justificativa.trim()) return;

                // Chama o método no componente Livewire pai (ListCursos)
                $wire.salvarJustificativa(this.editandoId, this.justificativa)
                    .then(() => {
                        // Sucesso: Adiciona ID à lista de justificados para ficar verde
                        this.frequenciasJustificadas.push(this.editandoId);
                        this.cancelar();
                    });
            }
        }"
        class="flex flex-col h-full p-4 bg-white border rounded-lg shadow-sm border-gray-200 dark:bg-gray-900 dark:border-white/10 relative">

        {{-- Cabeçalho: Dados do Aluno --}}
        @if(isset($aluno['nome']))
        <div class="mb-3">
            <div class="font-bold text-sm text-gray-900 dark:text-gray-100 line-clamp-1" title="{{ $aluno['nome'] }}">
                {{ $aluno['nome'] }}
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <p>CPF: {{ $aluno['cpf'] ?? '-' }}</p>
                <p class="truncate" title="{{ $aluno['email'] ?? '-' }}">
                    Email: {{ $aluno['email'] ?? '-' }}
                </p>
                <p class="truncate font-bold text-md text-gray-400" title="{{ $aluno['situacaoinscricao'] ?? '-' }}">
                    Situação: <span class="text-gray-700">{{ $aluno['situacaoinscricao'] ?? '-' }}</span>
                </p>
            </div>
        </div>

        {{-- Corpo: Frequências --}}
        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-white/5 align-top">
            <span class="block mb-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                Frequências
            </span>

            <div class="flex flex-wrap gap-1">
                @forelse($aluno['frequencias'] as $freq)

                {{-- Lógica de renderização dos badges com Alpine para troca de cor dinâmica --}}
                <template x-if="frequenciasJustificadas.includes({{ $freq['id'] }})">
                    {{-- Badge Verde (Visualmente atualizado após salvar) --}}
                    <div
                        class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">
                        {{ $freq['data'] }}
                    </div>
                </template>

                <template x-if="!frequenciasJustificadas.includes({{ $freq['id'] }})">
                    <span>
                        @if($freq['situacaofrequencia_id'] == config('settings.frequencia.presente'))
                        <div
                            class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">
                            {{ $freq['data'] }}
                        </div>

                        @elseif($freq['situacaofrequencia_id'] == config('settings.frequencia.ausente'))
                        {{-- Badge Vermelho vira Botão --}}
                        <button type="button" @click="abrirForm({{ $freq['id'] }})"
                            class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200 hover:bg-red-100 cursor-pointer transition">
                            {{ $freq['data'] }}
                        </button>
                        @else
                        <div class="m-0.5 px-2 py-1 text-xs font-medium text-center ring-1 bg-yellow-500">
                            {{ $freq['data'] }}
                        </div>
                        @endif
                    </span>
                </template>

                @empty
                <span class="text-xs italic text-gray-400">Sem registros</span>
                @endforelse
            </div>
        </div>

        {{-- Formulário Escondido (Aparece ao clicar) --}}
        <div x-show="editandoId" x-transition style="display: none;"
            class="absolute inset-0 z-10 bg-white dark:bg-gray-800 rounded-lg p-4 flex flex-col border-2 border-primary-500 shadow-lg">

            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">Justificar Ausência</h4>

            <textarea x-ref="inputJustificativa" x-model="justificativa"
                class="flex-1 w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 resize-none mb-3"
                placeholder="Informe o motivo..."></textarea>

            <div class="flex justify-end gap-2">
                <button @click="cancelar()" type="button"
                    class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    Cancelar
                </button>
                <button @click="salvar()" type="button" wire:loading.attr="disabled"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <span wire:loading.remove target="salvarJustificativa">Salvar</span>
                    <span wire:loading target="salvarJustificativa">...</span>
                </button>
            </div>
        </div>
        @endif
    </div>
    @empty
    <div class="col-span-full text-center text-sm text-gray-500 py-4">
        Nenhum aluno vinculado.
    </div>
    @endforelse
</div>
