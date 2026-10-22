<x-relatorios::pagina :titulo="$titulo" secao="Seção de Material de Consumo"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['saldo_anterior', 'entradas', 'saidas', 'saldo_atual']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'produto' => ['label' => 'Cód. Nat. Despesa'],
            'descricao' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '35%'],
            'saldo_anterior' => ['label' => 'Saldo Anterior', 'align' => 'right', 'fmt' => 'moeda'],
            'entradas' => ['label' => 'Entradas', 'align' => 'right', 'fmt' => 'moeda'],
            'saidas' => ['label' => 'Saídas', 'align' => 'right', 'fmt' => 'moeda'],
            'saldo_atual' => ['label' => 'Saldo Atual', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
