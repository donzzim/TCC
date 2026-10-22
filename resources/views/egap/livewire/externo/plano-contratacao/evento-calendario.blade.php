@php
    /** @var \App\Models\egap\PlanoContratacao\BemServico|null $item */
@endphp

@if (! $item)
    <p class="text-sm text-gray-500 dark:text-gray-400">Contratação não encontrada.</p>
@else
    <dl class="divide-y divide-gray-100 text-sm dark:divide-white/10">
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Nome</dt>
            <dd class="font-medium text-right">{{ $item->nome }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Justificativa</dt>
            <dd class="text-right">{{ $item->justificativa }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Unidade Organizacional</dt>
            <dd class="text-right">{{ $item->plano?->coordenadoriaRef?->unidadeorganizacional ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Valor final</dt>
            <dd class="text-right">R$ {{ number_format((float) $item->valor_final, 2, ',', '.') }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Prioridade</dt>
            <dd class="text-right">{{ ucfirst((string) $item->prioridade) }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Data estimada</dt>
            <dd class="text-right">{{ optional($item->data_estimada)->format('d/m/Y') ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Início dos procedimentos</dt>
            <dd class="text-right">{{ optional($item->data_inicio)->format('d/m/Y') ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">Data executada</dt>
            <dd class="text-right">{{ optional($item->data_executada)->format('d/m/Y') ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-4 py-2">
            <dt class="text-gray-500 dark:text-gray-400">n° do processo SEI</dt>
            <dd class="text-right">{{ $item->num_processo_sei ?: '—' }}</dd>
        </div>
    </dl>
@endif
