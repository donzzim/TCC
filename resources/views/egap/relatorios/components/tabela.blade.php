@props([
    'colunas',
    'linhas',
    'agruparPor' => null,
    'somar' => [],
    'total' => false,
    'totalLabel' => 'TOTAL',
    'subtotalLabel' => 'Subtotal',
    'totalLabelSpan' => null,
    'grupoRotulo' => null,
    'vazio' => 'Nenhum registro encontrado.',
])

@php
    $cols = [];
    foreach ($colunas as $campo => $def) {
        $def = is_string($def) ? ['label' => $def] : $def;
        $cols[] = [
            'campo' => $def['campo'] ?? $campo,
            'label' => $def['label'] ?? (string) $campo,
            'align' => $def['align'] ?? 'left',
            'fmt' => $def['fmt'] ?? null,
            'width' => $def['width'] ?? null,
        ];
    }

    $fmt = static function ($valor, ?string $formato): string {
        if ($valor === null || $valor === '') {
            return $formato === null ? '' : ($formato === 'moeda' ? 'R$ 0,00' : '0');
        }

        return match ($formato) {
            'moeda' => 'R$ ' . number_format((float) $valor, 2, ',', '.'),
            'decimal' => number_format((float) $valor, 2, ',', '.'),
            'decimal4' => number_format((float) $valor, 4, ',', '.'),
            'inteiro' => number_format((float) $valor, 0, '', '.'),
            default => (string) $valor,
        };
    };

    $linhas = collect($linhas);
    $somar = array_values((array) $somar);
    $span = $totalLabelSpan ?? max(1, count($cols) - count($somar));
    $grupos = $agruparPor ? $linhas->groupBy($agruparPor) : collect(['' => $linhas]);
    $totais = array_fill_keys($somar, 0.0);
@endphp

<div class="report-table-wrap">
    <table class="rel">
        <thead>
        <tr>
            @foreach ($cols as $coluna)
                <th
                    class="a-{{ $coluna['align'] }}"
                    scope="col"
                    @if ($coluna['width']) style="width: {{ $coluna['width'] }}" @endif
                >{{ $coluna['label'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse ($grupos as $grupoValor => $grupoLinhas)
            @if ($agruparPor)
                <tr class="grupo">
                    <td colspan="{{ count($cols) }}">
                        {{ $grupoRotulo ? $grupoRotulo($grupoValor, (array) $grupoLinhas->first()) : ($grupoValor !== '' ? $grupoValor : '—') }}
                    </td>
                </tr>
            @endif

            @php $subtotais = array_fill_keys($somar, 0.0); @endphp

            @foreach ($grupoLinhas as $linha)
                {{-- Linha numa só linha de markup: com indentação por célula, 100 mil linhas
                     viravam ~115 MB de HTML (só de espaço em branco eram ~40 MB). --}}
                <tr>@foreach ($cols as $coluna)<td class="a-{{ $coluna['align'] }}">{{ $fmt(data_get($linha, $coluna['campo']), $coluna['fmt']) }}</td>@endforeach</tr>
                @php
                    foreach ($somar as $campoSomado) {
                        $valor = (float) data_get($linha, $campoSomado);
                        $subtotais[$campoSomado] += $valor;
                        $totais[$campoSomado] += $valor;
                    }
                @endphp
            @endforeach

            @if ($agruparPor && $somar)
                <tr class="subtotal">
                    <td class="a-right" colspan="{{ $span }}">{{ $subtotalLabel }}</td>
                    @foreach (array_slice($cols, $span) as $coluna)
                        <td class="a-{{ $coluna['align'] }}">
                            {{ in_array($coluna['campo'], $somar, true) ? $fmt($subtotais[$coluna['campo']], $coluna['fmt'] ?? 'decimal') : '' }}
                        </td>
                    @endforeach
                </tr>
            @endif
        @empty
            <tr>
                <td class="vazio" colspan="{{ count($cols) }}">{{ $vazio }}</td>
            </tr>
        @endforelse

        @if ($total !== false && $linhas->isNotEmpty())
            @php $valoresTotal = is_array($total) ? $total : $totais; @endphp
            <tr class="total">
                <td class="a-right" colspan="{{ $span }}">{{ $totalLabel }}</td>
                @foreach (array_slice($cols, $span) as $coluna)
                    <td class="a-{{ $coluna['align'] }}">
                        {{ array_key_exists($coluna['campo'], $valoresTotal) ? $fmt($valoresTotal[$coluna['campo']], $coluna['fmt'] ?? 'decimal') : '' }}
                    </td>
                @endforeach
            </tr>
        @endif
        </tbody>
    </table>
</div>
