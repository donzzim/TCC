@php
    use App\Models\egap\Patrimonio\BensMoveis\ArquivoDigital;

    $arquivoDigital = $termo->arquivoDigital;
    $situacao = (int) ($arquivoDigital?->situacao ?? -1);
    $cancelado = $situacao === ArquivoDigital::SITUACAO_CANCELADO;
    $validado = $situacao === ArquivoDigital::SITUACAO_VALIDADO;
    $comprovanteAnexado = filled($arquivoDigital?->arquivo_digital);
@endphp

<div class="space-y-4">
    <ol class="relative ms-3 border-s-2 border-gray-200 dark:border-gray-700">
        <li class="mb-8 ms-6">
            <span class="absolute -start-[9px] flex h-4 w-4 items-center justify-center rounded-full bg-success-500 ring-4 ring-white dark:ring-gray-900"></span>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Transferência solicitada</h4>
            <time class="mb-2 block text-xs font-normal text-gray-500 dark:text-gray-400">
                {{ optional($termo->date_time)->format('d/m/Y H:i') ?? '-' }}
            </time>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Termo de Responsabilidade n° {{ $termo->termo_completo }} gerado para a movimentação dos bens.
            </p>
        </li>

        @if ($cancelado)
            <li class="ms-6">
                <span class="absolute -start-[9px] flex h-4 w-4 items-center justify-center rounded-full bg-danger-500 ring-4 ring-white dark:ring-gray-900"></span>
                <h4 class="text-sm font-semibold text-danger-600 dark:text-danger-400">Termo cancelado</h4>
                <time class="mb-2 block text-xs font-normal text-gray-500 dark:text-gray-400">
                    {{ optional($arquivoDigital?->data_validacao)->format('d/m/Y H:i') ?? '-' }}
                </time>
                @if ($arquivoDigital?->observacao)
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $arquivoDigital->observacao }}
                    </p>
                @endif
            </li>
        @else
            <li class="mb-8 ms-6">
                <span class="absolute -start-[9px] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ ($comprovanteAnexado || $validado) ? 'bg-success-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Em rota / Termo pendente</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @if ($comprovanteAnexado)
                        Comprovante de retirada anexado. Aguardando entrega e/ou assinatura eletrônica do setor destinatário no Termo.
                    @else
                        Aguardando o comprovante de retirada e/ou a assinatura eletrônica do setor destinatário no Termo.
                    @endif
                </p>
            </li>

            <li class="ms-6">
                <span class="absolute -start-[9px] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ $validado ? 'bg-success-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Entregue / Termo validado</h4>
                <time class="mb-2 block text-xs font-normal text-gray-500 dark:text-gray-400">
                    {{ optional($arquivoDigital?->data_validacao)->format('d/m/Y H:i') ?? '-' }}
                </time>
            </li>
        @endif
    </ol>
</div>
