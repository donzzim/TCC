<div x-data="{ tab: 'dados_pessoais' }"
    class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">

    <div class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg border border-indigo-100 dark:border-indigo-800">
        <div class="flex items-center">
            <x-heroicon-o-information-circle class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2" />
            <span class="text-sm font-medium text-indigo-800 dark:text-indigo-300">
                Sua modalidade de contratação atual é: <strong class="uppercase">{{ $modalidade_nome }}</strong>.
                Preencha os campos correspondentes abaixo.
            </span>
        </div>
    </div>

    <nav class="flex overflow-x-auto space-x-6 mb-6 border-b border-gray-200 dark:border-gray-700">
        <button type="button" @click="tab = 'dados_pessoais'"
            :class="{ 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400': tab === 'dados_pessoais' }"
            class="py-3 px-1 font-semibold text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 whitespace-nowrap transition-colors">
            1. Dados Cadastrais
        </button>
        <button type="button" @click="tab = 'dados_contratacao'"
            :class="{ 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400': tab === 'dados_contratacao' }"
            class="py-3 px-1 font-semibold text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 whitespace-nowrap transition-colors">
            2. Dados da Contratação
        </button>
        <button type="button" @click="tab = 'arquivos'"
            :class="{ 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400': tab === 'arquivos' }"
            class="py-3 px-1 font-semibold text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 whitespace-nowrap transition-colors">
            3. Documentos Pessoais
        </button>
        <button type="button" @click="tab = 'termos'"
            :class="{ 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400': tab === 'termos' }"
            class="py-3 px-1 font-semibold text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 whitespace-nowrap transition-colors">
            4. Assinatura de Termos
        </button>
    </nav>

    <form wire:submit.prevent="salvarDocumentacao">

        <div x-show="tab === 'dados_pessoais'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div class="col-span-1 md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Completo</label>
                        <input type="text" value="{{ $contratacao->instrutor->name }}" readonly
                            class="mt-1 block w-full bg-gray-100 text-gray-500 border-gray-300 rounded-md shadow-sm sm:text-sm cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPF</label>
                        <input type="text" value="{{ $contratacao->instrutor->cpf }}" readonly
                            class="mt-1 block w-full bg-gray-100 text-gray-500 border-gray-300 rounded-md shadow-sm sm:text-sm cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail Pessoal</label>
                        <input type="text" value="{{ $contratacao->instrutor->email }}" readonly
                            class="mt-1 block w-full bg-gray-100 text-gray-500 border-gray-300 rounded-md shadow-sm sm:text-sm cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">RG</label>
                    <input type="text" wire:model="rg"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('rg')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone /
                        WhatsApp</label>
                    <input type="text" wire:model="telefone"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('telefone')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço Residencial
                        Completo</label>
                    <input type="text" wire:model="endereco_residencial"
                        placeholder="Ex: Rua A, 123, Bairro B, Vitória - ES"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('endereco_residencial')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Currículo Lattes
                        (Link)</label>
                    <input type="url" wire:model="lattes" placeholder="http://lattes.cnpq.br/..."
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('lattes')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="tab = 'dados_contratacao'"
                    class="px-5 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">Avançar</button>
            </div>
        </div>

        <div x-show="tab === 'dados_contratacao'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

            @if (Str::contains(strtolower($modalidade_nome), ['jurídica', 'pj']))
                <div
                    class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-bold mb-4 text-gray-800 dark:text-gray-200">Dados da Pessoa Jurídica</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Razão
                                Social</label>
                            <input type="text" wire:model="razao_social"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CNPJ</label>
                            <input type="text" wire:model="cnpj"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail da
                                PJ</label>
                            <input type="email" wire:model="email_pj"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm sm:text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço da
                                PJ</label>
                            <input type="text" wire:model="endereco_pj"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm sm:text-sm">
                        </div>
                    </div>
                </div>
            @endif

            @if (!Str::contains(strtolower($modalidade_nome), ['jurídica', 'pj']))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PIS / PASEP /
                            NIT</label>
                        <input type="text" wire:model="pis"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm sm:text-sm">
                    </div>
                </div>
            @endif

            @if (!Str::contains(strtolower($modalidade_nome), ['volunt']))
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Banco para
                            Pagamento</label>
                        <input type="text" wire:model="banco" placeholder="Ex: Banco do Brasil (001)"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Agência (com
                            dígito)</label>
                        <input type="text" wire:model="agencia"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Conta (com
                            dígito)</label>
                        <input type="text" wire:model="conta"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm sm:text-sm">
                    </div>
                </div>
            @endif

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Opções de Voo
                    (Opcional)</label>
                <p class="text-xs text-gray-500 mb-2">Se aplicável, informe data, horário, companhia aérea e nº do voo.
                </p>
                <textarea wire:model="opcoes_voo" rows="2"
                    class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" @click="tab = 'dados_pessoais'"
                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Voltar</button>
                <button type="button" @click="tab = 'arquivos'"
                    class="px-5 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">Avançar</button>
            </div>
        </div>

        <div x-show="tab === 'arquivos'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Anexe os documentos obrigatórios listados abaixo. Arquivos aceitos: PDF ou Imagens (JPG/PNG) até 5MB.
            </p>

            <div class="space-y-4">
                @foreach ($modelosUpload as $key => $doc)
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between p-5 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 shadow-sm hover:border-indigo-300 transition-colors">

                        <div class="flex p-4 mb-4 md:mb-0 w-full">
                            <div class="flex items-center space-x-2">
                                <h4 class="font-bold text-gray-800 dark:text-gray-200">{{ $doc['nome'] }}</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {!! $doc['conteudo'] !!}
                                </p>
                            </div>


                            @if (isset($uploads[$key]) && $uploads[$key] !== null)
                                <div class="mt-2 text-xs font-semibold text-green-600 flex items-center">
                                    <x-heroicon-o-check-circle class="w-4 h-4 mr-1" /> Arquivo selecionado e pronto
                                    para envio.
                                </div>
                            @endif
                        </div>

                        <div class="w-full md:w-auto flex flex-col items-start md:items-end">
                            <label class="block w-full cursor-pointer">
                                <span class="sr-only">Escolher arquivo</span>
                                <input type="file" wire:model.live="uploads.{{ $key }}"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer transition">
                            </label>

                            <div wire:loading wire:target="uploads.{{ $key }}"
                                class="text-xs font-medium text-indigo-600 mt-2 animate-pulse">
                                Processando anexo...
                            </div>

                            @error('uploads.' . $key)
                                <span class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" @click="tab = 'dados_contratacao'"
                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Voltar</button>
                <button type="button" @click="tab = 'termos'"
                    class="px-5 py-2 bg-indigo-600 text-white rounded-lg shadow hover:bg-indigo-700 transition">Avançar</button>
            </div>
        </div>

        <div x-show="tab === 'termos'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" style="display: none;">

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Abaixo estão os formulários legais gerados pelo
                sistema com base nos seus dados. Leia-os com atenção e assine-os marcando a caixa de seleção.</p>

            <div class="space-y-5 mb-8">
                @foreach ($documentosProcesso->where('modelo.contexto', 'termo_gerado') as $doc)
                    <div
                        class="border rounded-lg bg-white dark:bg-gray-800 overflow-hidden shadow-sm {{ $doc->status_aprovacao === 'rejeitado' ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-200 dark:border-gray-700' }}">

                        <div
                            class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800/80">
                            <h4 class="font-bold text-gray-800 dark:text-gray-200">{{ $doc->modelo->nome }}</h4>

                            <div x-data="{ open: false }">
                                <button type="button" @click="open = true"
                                    class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    <x-heroicon-s-book-open class="w-4 h-4 mr-1" /> Ler Documento
                                </button>

                                <div x-show="open"
                                    class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center p-4 z-50"
                                    x-transition.opacity>
                                    <div @click.away="open = false"
                                        class="bg-white dark:bg-gray-900 p-8 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col">
                                        <div
                                            class="flex-1 overflow-y-auto prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300">
                                            {!! $doc->conteudo_gerado !!}
                                        </div>
                                        <div
                                            class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                            <button type="button" @click="open = false"
                                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">Fechar
                                                Leitura</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            @if ($doc->status_aprovacao === 'rejeitado')
                                <div class="mb-3 p-3 bg-red-50 text-red-700 rounded border border-red-200 text-sm">
                                    <strong>Devolvido pelo RH. Motivo:</strong> {{ $doc->motivo_rejeicao }}
                                </div>
                            @endif

                            <label class="flex items-start cursor-pointer group">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" wire:model="termos_aceitos.{{ $doc->id }}"
                                        class="w-5 h-5 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2 transition">
                                </div>
                                <div class="ml-3 text-sm">
                                    <span
                                        class="font-medium text-gray-900 dark:text-gray-200 group-hover:text-indigo-600 transition">
                                        Declaro que li e estou de acordo com os termos deste documento.
                                    </span>
                                    <p class="text-gray-500 mt-1">Ao marcar esta opção, você assina eletronicamente
                                        este formulário com validade jurídica.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                <button type="button" @click="tab = 'arquivos'"
                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Voltar</button>
                <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white font-bold rounded-lg shadow-md hover:bg-green-700 transition flex items-center">
                    <svg wire:loading wire:target="salvarDocumentacao"
                        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Submeter Documentação Completa
                </button>
            </div>
        </div>

    </form>
</div>
