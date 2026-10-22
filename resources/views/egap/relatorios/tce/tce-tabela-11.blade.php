<x-relatorios::pagina :titulo="$titulo" secao="Seção de Patrimônio"
    :filtros="$filtros" :data-emissao="$data_emissao">
    <x-relatorios::tabela
        :linhas="$dados"
        :somar="['entrada_incorporada', 'entrada_doacao', 'entrada_outros', 'total_entrada', 'saida_doacao', 'saida_perda', 'saida_outra', 'total_saida']"
        :total="true"
        :totalLabelSpan="3"
        :colunas="[
            'conta_contabil' => ['label' => 'Conta Contábil', 'align' => 'center'],
            'produto' => ['label' => 'Cód. Nat. Despesa'],
            'descricao' => ['label' => 'Descrição p/ Subitem Contábil', 'width' => '20%'],
            'entrada_incorporada' => ['label' => 'Ent. Incorporadas', 'align' => 'right', 'fmt' => 'moeda'],
            'entrada_doacao' => ['label' => 'Ent. Doação', 'align' => 'right', 'fmt' => 'moeda'],
            'entrada_outros' => ['label' => 'Ent. Outras', 'align' => 'right', 'fmt' => 'moeda'],
            'total_entrada' => ['label' => 'Total Entradas', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_doacao' => ['label' => 'Saí. Doação', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_perda' => ['label' => 'Saí. Perdas', 'align' => 'right', 'fmt' => 'moeda'],
            'saida_outra' => ['label' => 'Saí. Outras', 'align' => 'right', 'fmt' => 'moeda'],
            'total_saida' => ['label' => 'Total Saídas', 'align' => 'right', 'fmt' => 'moeda'],
        ]" />
</x-relatorios::pagina>
