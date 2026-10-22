<div x-data="{ mode: 'view' }">

    {{-- Navegação Interna --}}
    <div class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
        <div class="flex space-x-4">
            <button type="button" @click="mode = 'view'"
                :class="{ 'text-primary-600 font-bold border-b-2 border-primary-600': mode === 'view', 'text-gray-500': mode !== 'view' }"
                class="pb-1 mx-2">
                Visualizar
            </button>
            <button type="button" @click="mode = 'edit'"
                :class="{ 'text-primary-600 font-bold border-b-2 border-primary-600': mode === 'edit', 'text-gray-500': mode !== 'edit' }"
                class="pb-1 mx-2">
                Editar
            </button>
            @if ($this->documentacao && $this->documentacao->exists)
                <button type="button" @click="mode = 'history'"
                    :class="{ 'text-primary-600 font-bold border-b-2 border-primary-600': mode === 'history', 'text-gray-500': mode !== 'history' }"
                    class="pb-1 mx-2">
                    Histórico
                </button>
            @endif
        </div>

        {{-- Info de quem atualizou --}}
        @if ($this->documentacao && ($ultimo = $this->documentacao->ultimaAtualizacao()))
            <div class="text-xs text-gray-500">
                Última atualização: <span class="font-semibold">{{ $ultimo->user->name }}</span>
                em {{ $ultimo->created_at->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>

    {{-- MODO VISUALIZAÇÃO (Leitura) --}}
    <div x-show="mode === 'view'" class="prose dark:prose-invert max-w-none">
        @if ($this->documentacao)
            <h2 class="text-xl font-bold mb-2">{{ $this->documentacao->titulo }}</h2>
            <div class="mt-4">
                {!! $this->documentacao->conteudo !!}
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p>Nenhum conteúdo criado para esta página ainda.</p>
                <p class="text-sm">Clique em "Editar" para criar a primeira versão.</p>
            </div>
        @endif
    </div>

    {{-- MODO EDIÇÃO (Formulário Filament) --}}
    <div x-show="mode === 'edit'">
        <div class="alert alert-info bg-blue-50 text-blue-700 p-2 text-sm rounded mb-4">
            Você está editando a Wiki desta página. Você pode colar imagens (Ctrl+V), fazer upload de vídeos ou inserir
            links do YouTube.
        </div>

        {{-- Renderiza o formulário definido no PHP --}}
        {{ $this->form }}

        {{-- BOTÃO SALVAR (Adicionado aqui para responder ao AlpineJS) --}}
        <div class="mt-4 flex justify-end">
            <x-filament::button wire:click="salvarWiki" color="primary">
                Salvar Alterações
            </x-filament::button>
        </div>
    </div>

    {{-- MODO HISTÓRICO --}}
    <div x-show="mode === 'history'" class="mt-4">
        @if (isset($this->historico) && $this->historico->count() > 0)

            <ol class="relative border-s border-gray-200 dark:border-gray-700 ml-3">
                @foreach ($this->historico as $h)
                    <li class="mb-10 ms-6">
                        {{-- AVATAR / MARCADOR --}}
                        <span
                            class="absolute flex items-center justify-center w-8 h-8 rounded-full -start-4 ring-8 ring-white dark:ring-gray-900 bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            @if ($h->user->avatar_url)
                                {{-- Se o user tiver avatar (padrão filament ou jetstream) --}}
                                <img class="rounded-full shadow-lg w-full h-full object-cover"
                                    src="{{ Storage::url($h->user->avatar_url) }}" alt="{{ $h->user->name }} image" />
                            @else
                                {{-- Fallback: Ícone de usuário --}}
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z" />
                                </svg>
                            @endif
                        </span>

                        {{-- CARD DE CONTEÚDO --}}
                        <div
                            class="m-2 p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <div class="items-center justify-between mb-3 sm:flex">
                                {{-- Nome do Usuário --}}
                                <div class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                    Atualizado por <a href="#"
                                        class="font-semibold text-gray-900 dark:text-white hover:underline">{{ $h->user->name }}</a>
                                </div>

                                {{-- Tempo Relativo (ex: 2 horas atrás) --}}
                                <time
                                    class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400 sm:order-last sm:mb-0 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600">
                                    {{ $h->created_at->diffForHumans() }}
                                </time>
                            </div>

                            {{-- Data Exata e Detalhes --}}
                            <div
                                class="p-3 text-xs italic font-normal text-gray-500 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 dark:text-gray-400">
                                A versão foi salva em <strong>{{ $h->created_at->format('d/m/Y') }}</strong> às
                                <strong>{{ $h->created_at->format('H:i') }}</strong>.
                            </div>

                            {{-- Botões de Ação (Opcional - Descomente se for usar no futuro) --}}
                            {{--
                    <div class="flex items-center space-x-3 mt-4">
                        <button type="button"
                            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-xs px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                            Ver Detalhes
                        </button>
                    </div>
                    --}}
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="text-center py-6">
                <div
                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum histórico de alteração registrado.</p>
            </div>
        @endif
    </div>

</div>
