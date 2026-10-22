@php
    // O Service ja devolve linhas de subtotal (material = null) e total geral
    // (contabil e material = null). Traduzimos os nulls em rotulos.
    $linhas = collect($dados)->map(fn ($r) => [
        'contabil' => $r['contabil'] ?? 'TOTAL GERAL',
        'material' => $r['material'] ?? ($r['contabil'] === null ? '' : 'SUB TOTAL'),
        'qtde' => $r['material'] === null ? null : $r['qtde'],
        'valor' => $r['valor'],
    ]);
@endphp

<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$linhas"
        :colunas="[
            'contabil' => ['label' => 'Elemento de Despesa'],
            'material' => ['label' => 'Material', 'width' => '45%'],
            'qtde' => ['label' => 'Quantidade', 'align' => 'right', 'fmt' => 'inteiro'],
            'valor' => ['label' => 'Valor', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
