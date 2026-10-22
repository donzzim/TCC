@php
    $total = $linhas->reduce(fn (array $acc, array $linha): array => [
        'entrega' => $acc['entrega'] + $linha['entrega'],
        'manutencao' => $acc['manutencao'] + $linha['manutencao'],
        'devolucao' => $acc['devolucao'] + $linha['devolucao'],
        'transferencia' => $acc['transferencia'] + $linha['transferencia'],
        'total' => $acc['total'] + $linha['total'],
    ], ['entrega' => 0, 'manutencao' => 0, 'devolucao' => 0, 'transferencia' => 0, 'total' => 0]);
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 text-left dark:border-white/10">
                <th class="py-2 pe-4 font-semibold text-gray-950 dark:text-white">Setor</th>
                <th class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">Entrega</th>
                <th class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">Manutenção</th>
                <th class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">Devolução</th>
                <th class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">Transferência</th>
                <th class="py-2 ps-2 text-center font-semibold text-gray-950 dark:text-white">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($linhas as $linha)
                <tr class="border-b border-gray-100 dark:border-white/5">
                    <td class="py-2 pe-4 text-gray-700 dark:text-gray-300">{{ $linha['setor'] }}</td>
                    <td class="py-2 px-2 text-center text-gray-700 dark:text-gray-300">{{ $linha['entrega'] }}</td>
                    <td class="py-2 px-2 text-center text-gray-700 dark:text-gray-300">{{ $linha['manutencao'] }}</td>
                    <td class="py-2 px-2 text-center text-gray-700 dark:text-gray-300">{{ $linha['devolucao'] }}</td>
                    <td class="py-2 px-2 text-center text-gray-700 dark:text-gray-300">{{ $linha['transferencia'] }}</td>
                    <td class="py-2 ps-2 text-center font-semibold text-gray-950 dark:text-white">{{ $linha['total'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500 dark:text-gray-400">Nenhum dado encontrado.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($linhas->isNotEmpty())
            <tfoot>
                <tr class="border-t-2 border-gray-200 dark:border-white/10">
                    <td class="py-2 pe-4 font-semibold text-gray-950 dark:text-white">Total</td>
                    <td class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">{{ $total['entrega'] }}</td>
                    <td class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">{{ $total['manutencao'] }}</td>
                    <td class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">{{ $total['devolucao'] }}</td>
                    <td class="py-2 px-2 text-center font-semibold text-gray-950 dark:text-white">{{ $total['transferencia'] }}</td>
                    <td class="py-2 ps-2 text-center font-semibold text-gray-950 dark:text-white">{{ $total['total'] }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
