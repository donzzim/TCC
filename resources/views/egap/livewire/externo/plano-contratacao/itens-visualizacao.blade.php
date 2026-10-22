@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\egap\PlanoContratacao\BemServico> $itens */
    $rotulo = \App\Filament\Egap\Externo\Groups\PlanoContratacao\Enums\SituacaoPlano::class;
@endphp

<div class="overflow-x-auto">
    @if ($itens->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Este plano não possui itens.</p>
    @else
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500 dark:text-gray-400">
            <tr class="border-b border-gray-200 dark:border-white/10">
                <th class="p-2">#</th>
                <th class="p-2">Natureza / Plano Orçamentário</th>
                <th class="p-2">Descrição</th>
                <th class="p-2">Classificação</th>
                <th class="p-2">Prioridade</th>
                <th class="p-2 text-right">Qtd.</th>
                <th class="p-2 text-right">Valor 1º Grau</th>
                <th class="p-2 text-right">Valor 2º Grau</th>
                <th class="p-2 text-right">Valor Final</th>
                <th class="p-2">Data Estimada</th>
                <th class="p-2">Renovação</th>
                <th class="p-2">Situação</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($itens as $item)
                <tr class="border-b border-gray-100 dark:border-white/5">
                    <td class="p-2">{{ $item->id }}</td>
                    <td class="p-2">
                        <div>{{ $item->id_naturezadespesa }} — {{ $item->naturezaDespesa?->DescricaodaClasse }}</div>
                        <div
                            class="text-xs text-gray-500 dark:text-gray-400">{{ $item->planoOrcamentario?->nome }}</div>
                    </td>
                    <td class="p-2">{{ $item->nome }}</td>
                    <td class="p-2">{{ ucfirst($item->classificacao) }}</td>
                    <td class="p-2">{{ ucfirst($item->prioridade) }}</td>
                    <td class="p-2 text-right">{{ $item->quantidade }}</td>
                    <td class="p-2 text-right">R$ {{ number_format((float) $item->valor1, 2, ',', '.') }}</td>
                    <td class="p-2 text-right">R$ {{ number_format((float) $item->valor2, 2, ',', '.') }}</td>
                    <td class="p-2 text-right font-medium">
                        R$ {{ number_format((float) $item->valor_final, 2, ',', '.') }}</td>
                    <td class="p-2">{{ optional($item->data_estimada)->format('d/m/Y') ?? '—' }}</td>
                    <td class="p-2">{{ $item->renovacao_contrato === 'sim' ? 'Sim' : 'Não' }}</td>
                    <td class="p-2">{{ $rotulo::rotulo((int) $item->id_etapa) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
