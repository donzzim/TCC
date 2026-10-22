<div
    class="space-y-4 text-sm mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
    <h3 class="font-bold text-gray-800 dark:text-gray-200 text-base">Análise Documental:
        {{ $instrutorPivot->instrutor->name }}</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-300">Dados Bancários/Fiscais:</span>
            <ul class="mt-1 text-gray-600 dark:text-gray-400">
                <li><strong>CNPJ:</strong> {{ $instrutorPivot->cnpj ?? 'N/A' }}</li>
                <li><strong>Razão Social:</strong> {{ $instrutorPivot->razao_social ?? 'N/A' }}</li>
                @if ($instrutorPivot->dados_bancarios)
                    <li><strong>Banco:</strong> {{ $instrutorPivot->dados_bancarios['banco'] ?? '-' }}</li>
                    <li><strong>Agência:</strong> {{ $instrutorPivot->dados_bancarios['agencia'] ?? '-' }}</li>
                    <li><strong>Conta:</strong> {{ $instrutorPivot->dados_bancarios['conta'] ?? '-' }}</li>
                @else
                    <li>Nenhum dado bancário preenchido.</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="mt-4">
        <span class="font-semibold text-gray-700 dark:text-gray-300">Documentos e Termos:</span>
        @if ($documentos->isEmpty())
            <p class="text-gray-500 mt-1">Nenhum documento gerado ou anexado ainda.</p>
        @else
            <ul
                class="mt-2 divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-md bg-white dark:bg-gray-900">
                @foreach ($documentos as $doc)
                    <li class="p-3 flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                {{ $doc->modelo->nome ?? 'Documento' }}
                            </span>
                            @if ($doc->is_assinado)
                                <span class="text-xs text-green-600 font-semibold mt-1">✓ Assinado digitalmente em
                                    {{ $doc->assinado_em->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="text-xs text-yellow-600 mt-1">Pendente de assinatura/anexo</span>
                            @endif
                        </div>

                        <div class="flex items-center">
                            @if ($doc->arquivoPessoal && $doc->arquivoPessoal->arquivo)
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($doc->arquivoPessoal->arquivo) }}"
                                    target="_blank"
                                    class="text-primary-600 hover:text-primary-500 text-sm font-medium underline">
                                    Visualizar Anexo
                                </a>
                            @elseif($doc->conteudo_gerado && $doc->is_assinado)
                                <span class="text-sm text-gray-500">Termo Gerado</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
