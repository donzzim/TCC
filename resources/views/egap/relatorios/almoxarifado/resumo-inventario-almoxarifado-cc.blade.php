@php
    $medidas = ['saldo_anterior', 'entradas', 'saidas', 'saldo_atual', 'saidas_acumuladas'];
    $colsMedidas = [
        'saldo_anterior' => ['label' => 'Saldo Anterior', 'align' => 'right', 'fmt' => 'moeda'],
        'entradas' => ['label' => 'Entradas', 'align' => 'right', 'fmt' => 'moeda'],
        'saidas' => ['label' => 'Saídas', 'align' => 'right', 'fmt' => 'moeda'],
        'saldo_atual' => ['label' => 'Saldo Atual', 'align' => 'right', 'fmt' => 'moeda'],
        'saidas_acumuladas' => ['label' => 'Saídas Acumuladas', 'align' => 'right', 'fmt' => 'moeda'],
    ];
@endphp

<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">

    <h2 class="bloco">Detalhamento por Centro de Custo</h2>
    <x-relatorios::tabela
        :linhas="$dados"
        agruparPor="centro_custo_codigo"
        :somar="$medidas"
        :totalLabelSpan="4"
        :grupoRotulo="fn ($v, $l) => 'Centro de Custo: ' . $v . ' — ' . ($l['centro_custo_descricao'] ?? '')"
        :colunas="array_merge([
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'produto' => ['label' => 'Cód. Nat. Despesa'],
            'item_patrimonial' => ['label' => 'Item Patrimonial'],
            'descricao' => ['label' => 'Descrição', 'width' => '30%'],
        ], $colsMedidas)" />

    <div class="quebra"></div>
    <h2 class="bloco">Resumo por Centro de Custo</h2>
    <x-relatorios::tabela
        :linhas="$resumos['por_centro_custo']"
        :somar="$medidas"
        :total="true"
        :totalLabelSpan="2"
        :colunas="array_merge([
            'codigo' => ['label' => 'Código', 'align' => 'center'],
            'centro_custo' => ['label' => 'Centro de Custo', 'width' => '30%'],
        ], $colsMedidas)" />

    <div class="quebra"></div>
    <h2 class="bloco">Resumo por Conta Contábil</h2>
    <x-relatorios::tabela
        :linhas="$resumos['por_conta']"
        :somar="$medidas"
        :total="true"
        :totalLabelSpan="3"
        :colunas="array_merge([
            'conta_contabil' => ['label' => 'Código', 'align' => 'center'],
            'produto' => ['label' => 'Produto'],
            'descricao' => ['label' => 'Descrição', 'width' => '25%'],
        ], $colsMedidas)" />

    <div class="quebra"></div>
    <h2 class="bloco">Resumo por Item Patrimonial</h2>
    <x-relatorios::tabela
        :linhas="$resumos['por_item_patrimonial']"
        :somar="$medidas"
        :total="true"
        :totalLabelSpan="1"
        :colunas="array_merge([
            'item_patrimonial' => ['label' => 'Item Patrimonial'],
        ], $colsMedidas)" />
</x-relatorios::pagina>
