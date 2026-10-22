<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['entradas', 'entradas_doacao', 'saidas', 'saidas_doacao']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'produto' => ['label' => 'Cód. Nat. Despesa'],
            'descricao' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '40%'],
            'entradas' => ['label' => 'Compras', 'align' => 'right', 'fmt' => 'moeda'],
            'entradas_doacao' => ['label' => 'Doação / Transferência', 'align' => 'right', 'fmt' => 'moeda'],
            'saidas' => ['label' => 'Consumo', 'align' => 'right', 'fmt' => 'moeda'],
            'saidas_doacao' => ['label' => 'Saí. Doação / Transf.', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
